<?= view('layouts/head') ?>

<body class="bg-light">
    <div id="layout-wrapper">
        <?= view('layouts/header') ?>
        <?= view('layouts/sidebar') ?>

        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">

                    <div class="row mb-3">
                        <div class="col-md-8">
                            <h4 class="text-primary fw-bold">Bank Payment Check</h4>
                        </div>
                        <div class="col-md-4 text-end">
                            <h6 class="text-secondary"><?= date('Y-m-d') ?></h6>
                            <a href="<?= base_url('/dashboard2/tiles') ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-arrow-left-circle me-1"></i>Back to Dashboard
                            </a>
                        </div>
                    </div>

                    <!-- Filter -->
                    <div class="card shadow-sm border-0 rounded mb-4">
                        <div class="card-body py-3">
                            <form method="GET" action="" class="row g-3 align-items-end">
                                <div class="col-md-3 d-none">
                                    <label class="form-label fw-semibold">Payment Date</label>
                                    <input type="date" name="date" class="form-control"
                                        value="<?= esc($filter_date) ?>">
                                </div>
                                <div class="col-md-2 d-none">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="bi bi-search me-1"></i>Filter
                                    </button>
                                </div>
                                <div class="col-md-2 d-none">
                                    <a href="?date=<?= date('Y-m-d') ?>" class="btn btn-outline-secondary w-100">
                                        <i class="bi bi-arrow-clockwise me-1"></i>Today
                                    </a>
                                </div>
                                <div class="col-md-2">
                                    <a href="<?= base_url('report/bank_payment_check/finalized_records') ?>" class="btn btn-outline-primary w-100">
                                        <i class="bi bi-card-checklist me-1"></i>Finalized Records
                                    </a>
                                </div>
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-outline-success w-100" onclick="window.print()">
                                        <i class="bi bi-printer me-1"></i>Print
                                    </button>
                                </div>
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-success w-100" id="finalizeSelectedBtn" disabled>
                                        <i class="bi bi-check2-all me-1"></i>Finalize Selected
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Total Pending -->
                    <!-- <div class="row mb-4">
                        <div class="col-12">
                            <a href="<?//= base_url('tour/tour_accounts/payment_requested_dashboard') ?>" class="text-decoration-none">
                                <div class="alert alert-danger py-3 mb-0 d-flex align-items-center justify-content-end gap-3">
                                    <span class="fw-semibold fs-6 text-danger">
                                        <i class="bi bi-hourglass-split me-2"></i>Total Pending
                                    </span>
                                    <span class="badge bg-danger fs-5 px-3 py-2"><?= (int)($count_pending ?? 0) ?></span>
                                </div>
                            </a>
                        </div>
                    </div> -->

                    <!-- Summary -->
                    <!-- <div class="row mb-3">
                        <div class="col-md-3">
                            <div class="card border-0 shadow-sm text-center py-3">
                                <div class="text-muted small">Total Payments</div>
                                <div class="fw-bold fs-4 text-primary"><?//= count($payments) ?></div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-0 shadow-sm text-center py-3">
                                <div class="text-muted small">Total Amount (LKR)</div>
                                <div class="fw-bold fs-5 text-success">
                                    <?//= number_format(array_sum(array_column($payments, 'Amount')), 2) ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-0 shadow-sm text-center py-3">
                                <div class="text-muted small">Date</div>
                                <div class="fw-bold fs-6 text-dark"><?//= esc($filter_date) ?></div>
                            </div>
                        </div>
                    </div> -->

                    <!-- Table -->
                    <div class="card shadow-sm border-0 rounded">
                        <div class="card-header bg-white border-bottom" style="display: none;">
                            <h6 class="mb-0 fw-semibold">Payments — <?= esc($filter_date) ?></h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover align-middle mb-0" id="paymentTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th>
                                                <input type="checkbox" id="selectAll" title="Select all">
                                            </th>
                                            <th>#</th>
                                            <th>Voucher No</th>
                                            <th>Payee</th>
                                            <th>Payee Bank Details</th>
                                            <th>Type</th>
                                            <th>Bank</th>
                                            <th class="text-end">Amount (LKR)</th>
                                            <th>Invoice Date</th>
                                            <th>Finalize</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($payments)): ?>
                                            <?php
                                            $i = 1;
                                            $current_tour = null;
                                            $group_lkr = 0;
                                            $tour_vnums = [];
                                            ?>
                                            <?php foreach ($payments as $row): ?>
                                                <?php if ($row['tour_id'] !== $current_tour): ?>
                                                    <?php if ($current_tour !== null): ?>
                                                        <tr class="table-secondary fw-semibold">
                                                            <td></td>
                                                            <td colspan="6" class="text-end fst-italic">Tour Total</td>
                                                            <td class="text-end text-success"><?= number_format($group_lkr, 2) ?></td>
                                                            <td></td>
                                                        </tr>
                                                    <?php endif; ?>
                                                    <?php
                                                    $current_tour = $row['tour_id'];
                                                    $group_lkr = 0;
                                                    ?>
                                                    <tr class="table-primary">
                                                        <td>
                                                            <input type="checkbox" class="tour-select-all" data-tour="<?= esc($row['tour_id']) ?>" title="Select all in this tour">
                                                        </td>
                                                        <td colspan="8">
                                                            <strong><?= esc($row['tour_id']) ?></strong>
                                                            <?php if ($row['manual_id']): ?>
                                                                &nbsp;·&nbsp;<span class="text-dark"><?= esc($row['manual_id']) ?></span>
                                                            <?php endif; ?>
                                                            <?php if ($row['tour_name']): ?>
                                                                &nbsp;·&nbsp;<?= esc($row['tour_name']) ?>
                                                            <?php endif; ?>
                                                            <?php if ($row['agent_name']): ?>
                                                                &nbsp;<span class="text-muted small">(<?= esc($row['agent_name']) ?>)</span>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endif; ?>
                                                <?php $group_lkr += (float)$row['Amount']; ?>
                                                <tr>
                                                    <td>
                                                        <input type="checkbox" class="row-select" data-tour="<?= esc($row['tour_id']) ?>" value="<?= esc($row['Vnum']) ?>"
                                                            data-status="<?= (int)$row['status'] ?>"
                                                            data-id="<?= esc($row['id']) ?>"
                                                            data-bank-acc="<?= esc($row['Bank_acc']) ?>"
                                                            data-type="<?= esc($row['v_typ']) ?>"
                                                            data-amount="<?= esc($row['Amount']) ?>"
                                                            data-sup="<?= esc($row['sup']) ?>"
                                                            data-payee="<?= esc($row['payee']) ?>">
                                                    </td>
                                                    <td><?= $i++ ?></td>
                                                    <td><strong><?= esc($row['Vnum']) ?></strong></td>
                                                    <td>
                                                        <?= esc($row['payee']) ?>
                                                        <?php if (!empty($row['hotel_code'])): ?>
                                                            <br><span class="badge bg-info text-dark"><?= esc($row['hotel_code']) ?></span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if (!empty($row['sup_bank_acc'])): ?>
                                                            <div class="small">
                                                                <div><strong><?= esc($row['sup_bank_acc']) ?></strong></div>
                                                                <?php if (!empty($row['sup_bank_name'])): ?>
                                                                    <div class="text-muted"><?= esc($row['sup_bank_name']) ?><br><?= !empty($row['sup_branch_name']) ? esc($row['sup_branch_name']) : '' ?></div>
                                                                    
                                                                <?php endif; ?>
                                                                
                                                                <?php if (!empty($row['sup_acc_holder'])): ?>
                                                                    <div class="text-muted"><?= esc($row['sup_acc_holder']) ?></div>
                                                                <?php endif; ?>
                                                            </div>
                                                        <?php else: ?>
                                                            <span class="text-muted small">—</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-<?= ($row['payment_type'] ?? '') === 'Transport' ? 'primary' : 'warning text-dark' ?>">
                                                            <?= esc($row['payment_type'] ?? 'Payment') ?>
                                                        </span>
                                                    </td>
                                                    <td><?= esc($row['Bank']) ?></td>
                                                    <td class="text-end text-success fw-semibold">
                                                        <?= number_format((float)$row['Amount'], 2) ?>
                                                    </td>
                                                    <td><?= $row['pay_date'] ? date('Y-m-d', strtotime($row['pay_date'])) : '-' ?></td>
                                                    <td>
                                                        <?php if ($row['finalized'] == 1): ?>
                                                            <span class="badge bg-success">Finalized</span>
                                                            <br><small class="text-muted"><?= esc($row['finalized_user']) ?></small>
                                                            <br><small class="text-muted"><?= date('Y-m-d H:i', strtotime($row['finalized_date'])) ?></small>
                                                        <?php else: ?>
                                                            <button class="btn btn-sm btn-outline-success finalize-btn"
                                                                data-pr-id="<?= $row['Vnum'] ?>"
                                                                data-status="<?= (int)$row['status'] ?>"
                                                                data-id="<?= esc($row['id']) ?>"
                                                                data-bank-acc="<?= esc($row['Bank_acc']) ?>"
                                                                data-type="<?= esc($row['v_typ']) ?>"
                                                                data-amount="<?= esc($row['Amount']) ?>"
                                                                data-sup="<?= esc($row['sup']) ?>"
                                                                data-payee="<?= esc($row['payee']) ?>">
                                                                <i class="bi bi-check2-circle me-1"></i>Mark Finalized
                                                            </button>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                            <?php if ($current_tour !== null): ?>
                                                <tr class="table-secondary fw-semibold">
                                                    <td></td>
                                                    <td colspan="6" class="text-end fst-italic">Tour Total</td>
                                                    <td class="text-end text-success"><?= number_format($group_lkr, 2) ?></td>
                                                    <td></td>
                                                </tr>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="9" class="text-center text-muted py-4">
                                                    No payments found for <?= esc($filter_date) ?>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                    <?php if (!empty($payments)): ?>
                                    <tfoot class="table-light fw-semibold">
                                        <tr>
                                            <td></td>
                                            <td colspan="6" class="text-end">Grand Total</td>
                                            <td class="text-end text-success">
                                                <?= number_format(array_sum(array_column($payments, 'Amount')), 2) ?>
                                            </td>
                                            <td colspan="2"></td>
                                        </tr>
                                    </tfoot>
                                    <?php endif; ?>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Petty Cash Voucher Confirmation (collapsible) -->
                    <div class="card shadow-sm border-0 rounded mt-4">
                        <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center" style="cursor: pointer;" data-bs-toggle="collapse" data-bs-target="#pettyCashSection" aria-expanded="false" aria-controls="pettyCashSection">
                            <h6 class="mb-0 fw-bold" style="font-size:1rem;letter-spacing:0.01em;">
                                <i class="bi bi-cash-coin me-2"></i>Petty Cash Voucher Confirmation
                                <span style="background:#f59e0b;color:#fff;border-radius:20px;padding:3px 12px;font-size:0.8rem;font-weight:700;margin-left:8px;"><?= count($petty_cash_vouchers ?? []) ?> pending</span>
                            </h6>
                            <div class="d-flex align-items-center gap-2">
                                <span style="background:#dbeafe;color:#1d4ed8;border:2px solid #93c5fd;border-radius:8px;padding:5px 14px;font-size:0.85rem;font-weight:700;letter-spacing:0.02em;">
                                    Dr &nbsp;<span style="font-size:1rem;"><?= number_format($pc_total_dr, 2) ?></span>
                                </span>
                                <span style="background:#fee2e2;color:#b91c1c;border:2px solid #fca5a5;border-radius:8px;padding:5px 14px;font-size:0.85rem;font-weight:700;letter-spacing:0.02em;">
                                    Cr &nbsp;<span style="font-size:1rem;"><?= number_format($pc_total_cr, 2) ?></span>
                                </span>
                                <span style="background:<?= $pc_balance < 0 ? '#b91c1c' : '#15803d' ?>;color:#fff;border:2px solid <?= $pc_balance < 0 ? '#f87171' : '#4ade80' ?>;border-radius:8px;padding:5px 14px;font-size:0.85rem;font-weight:700;letter-spacing:0.02em;">
                                    Balance &nbsp;<span style="font-size:1rem;"><?= number_format($pc_balance, 2) ?></span>
                                </span>
                                <i class="bi bi-chevron-down ms-2"></i>
                            </div>
                        </div>
                        <div class="collapse" id="pettyCashSection">
                            <div class="card-body py-3">
                                <div class="mb-3">
                                    <a href="<?php echo base_url('report/petty_cash'); ?>" class="btn btn-primary btn-sm" target="_blank">
                                        <i class="bi bi-file-earmark-text"></i> View Report
                                    </a>
                                </div>

                                <?php if (!empty($petty_cash_vouchers)): ?>
                                <div class="d-flex justify-content-end mb-2">
                                    <button type="button" class="btn btn-success btn-sm" id="confirmPettySelectedBtn" disabled>
                                        <i class="bi bi-check2-all me-1"></i>Confirm Selected
                                    </button>
                                </div>
                                <?php endif; ?>
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover align-middle mb-0" id="pettyCashTable">
                                        <thead class="table-light">
                                            <tr>
                                                <th>
                                                    <input type="checkbox" id="pettySelectAll" title="Select all">
                                                </th>
                                                <th>#</th>
                                                <th>Voucher No</th>
                                                <th>Pay</th>
                                                <th>Reference No</th>
                                                <th class="text-end">Amount</th>
                                                <th>Requested From</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($petty_cash_vouchers)): ?>
                                                <?php $p = 1; ?>
                                                <?php foreach ($petty_cash_vouchers as $pv): ?>
                                                    <tr>
                                                        <td>
                                                            <input type="checkbox" class="petty-row-select" value="<?= esc($pv->Voucher) ?>"
                                                                data-id="<?= esc($pv->id) ?>"
                                                                data-bank-acc="<?= esc($pv->Bank_acc) ?>"
                                                                data-type="<?= esc($pv->Pay_Type) ?>"
                                                                data-amount="<?= esc($pv->Amount) ?>"
                                                                data-sup="<?= esc($pv->sup) ?>"
                                                                data-payee="<?= esc($pv->Pay) ?>">
                                                        </td>
                                                        <td><?= $p++ ?></td>
                                                        <td><strong><?= esc($pv->Voucher) ?></strong></td>
                                                        <td><?= esc($pv->Pay) ?></td>
                                                        <td><?= esc($pv->Chq_No) ?></td>
                                                        <td class="text-end text-success fw-semibold"><?= number_format((float)$pv->Amount, 2) ?></td>
                                                        <td><?= esc($pv->login) ?></td>
                                                        <td>
                                                            <button class="btn btn-sm btn-outline-success petty-confirm-btn"
                                                                data-id="<?= esc($pv->id) ?>"
                                                                data-bank-acc="<?= esc($pv->Bank_acc) ?>"
                                                                data-type="<?= esc($pv->Pay_Type) ?>"
                                                                data-amount="<?= esc($pv->Amount) ?>"
                                                                data-sup="<?= esc($pv->sup) ?>"
                                                                data-payee="<?= esc($pv->Pay) ?>"
                                                                data-pr-id="<?= esc($pv->Voucher) ?>">
                                                                <i class="bi bi-check2-circle me-1"></i>Confirm
                                                            </button>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="8" class="text-center text-muted py-3">No pending petty cash vouchers</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
            <?= view('layouts/brand') ?>
        </div>
    </div>

    <style>
        @media print {
            .no-print, #layout-wrapper > *:not(.main-content) { display: none !important; }
            .main-content { margin: 0 !important; }
            .card { box-shadow: none !important; }
        }
    </style>

    <?= view('layouts/inpage_footer') ?>
    <?= view('layouts/scripts') ?>

    <script src="<?= base_url() ?>assets/bundles/datatablescripts.bundle.js"></script>
    <!-- Bank Date Modal -->
    <div class="modal fade" id="bankDateModal" tabindex="-1" aria-labelledby="bankDateModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h6 class="modal-title" id="bankDateModalLabel"><i class="bi bi-calendar-check me-1"></i>Enter Bank Date</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label fw-semibold">Bank Date</label>
                    <input type="date" id="bankDateInput" class="form-control" value="<?= date('Y-m-d') ?>">
                    <div id="bankDateError" class="text-danger small mt-1 d-none">Please select a bank date.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success btn-sm" id="confirmFinalizeBtn">Confirm</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            $('#paymentTable').DataTable({
                pageLength: 25,
                order: [[1, 'asc']],
                columnDefs: [{ orderable: false, targets: [0, 8] }]
            });
        });

        // Track pending finalize action: { mode: 'single'|'bulk', prId, vnums }
        let pendingFinalize = null;

        function openBankDateModal(mode, data) {
            pendingFinalize = { mode, ...data };
            $('#bankDateError').addClass('d-none');
            $('#bankDateInput').val('<?= date('Y-m-d') ?>');
            $('#bankDateModal').modal('show');
        }

        // Confirm button in modal
        $('#confirmFinalizeBtn').on('click', function() {
            const bankDate = $('#bankDateInput').val();
            if (!bankDate) {
                $('#bankDateError').removeClass('d-none');
                return;
            }
            $('#bankDateModal').modal('hide');

            if (pendingFinalize.mode === 'single') {
                doSingleFinalize(pendingFinalize.prId, bankDate);
            } else {
                doBulkFinalize(pendingFinalize.vnums, bankDate, pendingFinalize.rows);
            }
        });

        // Single finalize
        $(document).on('click', '.finalize-btn', function() {
            const btn = $(this);
            const prId = btn.data('pr-id');
            openBankDateModal('single', {
                prId,
                btn,
                status: btn.data('status'),
                id: btn.data('id'),
                bankAcc: btn.data('bank-acc'),
                type: btn.data('type'),
                amount: btn.data('amount'),
                sup: btn.data('sup'),
                payee: btn.data('payee')
            });
        });

        function approveVoucher(pending, callback) {
            $.post('<?= base_url('accounts/voucher_auth/update') ?>', {
                update: 'data',
                id: pending.id,
                status: 1,
                bank_acc: pending.bankAcc,
                type: pending.type,
                amount: pending.amount,
                payee: pending.payee,
                voucher_no: pending.prId,
                sup: pending.sup,
            }, function(data) {
                if (data.status === 200) {
                    callback(true);
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Failed to approve voucher' });
                    callback(false);
                }
            }, 'json').fail(function() {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to approve voucher' });
                callback(false);
            });
        }

        function doSingleFinalize(prId, bankDate) {
            const btn = $(`.finalize-btn[data-pr-id="${prId}"]`);
            btn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Processing...');

            const finalizeAfterApprove = function() {
                $.post('<?= base_url('report/bank_payment_check/mark_finalized') ?>', {
                    pr_id: prId,
                    bank_date: bankDate
                }, function(data) {
                    if (data.status === 200) {
                        Swal.fire({ icon: 'success', title: 'Finalized!', showConfirmButton: false, timer: 1200 })
                            .then(() => location.reload());
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: data.message });
                        btn.prop('disabled', false).html('<i class="bi bi-check2-circle me-1"></i>Mark Finalized');
                    }
                }, 'json');
            };

            if (String(pendingFinalize.status) === '0') {
                approveVoucher(pendingFinalize, function(ok) {
                    if (ok) {
                        finalizeAfterApprove();
                    } else {
                        btn.prop('disabled', false).html('<i class="bi bi-check2-circle me-1"></i>Mark Finalized');
                    }
                });
                return;
            }

            $.post('<?= base_url('report/bank_payment_check/mark_finalized') ?>', {
                pr_id: prId,
                bank_date: bankDate
            }, function(data) {
                if (data.status === 200) {
                    Swal.fire({ icon: 'success', title: 'Finalized!', showConfirmButton: false, timer: 1200 })
                        .then(() => location.reload());
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: data.message });
                    btn.prop('disabled', false).html('<i class="bi bi-check2-circle me-1"></i>Mark Finalized');
                }
            }, 'json');
        }

        // Bulk finalize
        $('#finalizeSelectedBtn').on('click', function() {
            const vnums = getSelectedVnums();
            if (vnums.length === 0) return;
            openBankDateModal('bulk', { vnums, rows: getSelectedRows() });
        });

        function approveVouchersSequentially(rows, index, onDone) {
            if (index >= rows.length) {
                onDone(true);
                return;
            }
            const row = rows[index];
            if (String(row.status) !== '0') {
                approveVouchersSequentially(rows, index + 1, onDone);
                return;
            }
            approveVoucher({ id: row.id, bankAcc: row.bankAcc, type: row.type, amount: row.amount, payee: row.payee, prId: row.prId, sup: row.sup }, function(ok) {
                if (ok) {
                    approveVouchersSequentially(rows, index + 1, onDone);
                } else {
                    onDone(false);
                }
            });
        }

        function doBulkFinalize(vnums, bankDate, rows) {
            $('#finalizeSelectedBtn').prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Processing...');

            const finalizeSelected = function() {
                $.post('<?= base_url('report/bank_payment_check/bulk_finalize') ?>', {
                    vnums: vnums,
                    bank_date: bankDate
                }, function(data) {
                    if (data.status === 200) {
                        Swal.fire({ icon: 'success', title: 'Done!', text: data.message, showConfirmButton: false, timer: 1500 })
                            .then(() => location.reload());
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: data.message });
                        $('#finalizeSelectedBtn').prop('disabled', false).html('<i class="bi bi-check2-all me-1"></i>Finalize Selected');
                    }
                }, 'json');
            };

            approveVouchersSequentially(rows || [], 0, function(ok) {
                if (ok) {
                    finalizeSelected();
                } else {
                    $('#finalizeSelectedBtn').prop('disabled', false).html('<i class="bi bi-check2-all me-1"></i>Finalize Selected');
                }
            });
        }

        function getSelectedVnums() {
            return $('.row-select:checked').map(function() { return $(this).val(); }).get();
        }

        function getSelectedRows() {
            return $('.row-select:checked').map(function() {
                const el = $(this);
                return {
                    prId: el.val(),
                    status: el.data('status'),
                    id: el.data('id'),
                    bankAcc: el.data('bank-acc'),
                    type: el.data('type'),
                    amount: el.data('amount'),
                    sup: el.data('sup'),
                    payee: el.data('payee')
                };
            }).get();
        }

        function updateFinalizeBtn() {
            $('#finalizeSelectedBtn').prop('disabled', getSelectedVnums().length === 0);
        }

        // Select all (header)
        $('#selectAll').on('change', function() {
            $('.row-select, .tour-select-all').prop('checked', this.checked);
            updateFinalizeBtn();
        });

        // Tour-level select all
        $(document).on('change', '.tour-select-all', function() {
            const tour = $(this).data('tour');
            $(`.row-select[data-tour="${tour}"]`).prop('checked', this.checked);
            updateFinalizeBtn();
        });

        // Row-level checkbox
        $(document).on('change', '.row-select', function() {
            const tour = $(this).data('tour');
            const allInTour = $(`.row-select[data-tour="${tour}"]`).length;
            const checkedInTour = $(`.row-select[data-tour="${tour}"]:checked`).length;
            $(`.tour-select-all[data-tour="${tour}"]`).prop('checked', allInTour === checkedInTour);
            const total = $('.row-select').length;
            const totalChecked = $('.row-select:checked').length;
            $('#selectAll').prop('checked', total === totalChecked);
            updateFinalizeBtn();
        });

        // ===== Petty Cash confirmation (no bank date step) =====

        function confirmPettyVoucher(row, onDone) {
            approveVoucher(row, onDone);
        }

        // One-by-one confirm
        $(document).on('click', '.petty-confirm-btn', function() {
            const btn = $(this);
            const row = {
                prId: btn.data('pr-id'),
                id: btn.data('id'),
                bankAcc: btn.data('bank-acc'),
                type: btn.data('type'),
                amount: btn.data('amount'),
                sup: btn.data('sup'),
                payee: btn.data('payee')
            };

            Swal.fire({
                title: 'Confirm this petty cash voucher?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Confirm'
            }).then((result) => {
                if (!result.isConfirmed) return;

                btn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Processing...');
                confirmPettyVoucher(row, function(ok) {
                    if (ok) {
                        Swal.fire({ icon: 'success', title: 'Confirmed!', showConfirmButton: false, timer: 1200 })
                            .then(() => location.reload());
                    } else {
                        btn.prop('disabled', false).html('<i class="bi bi-check2-circle me-1"></i>Confirm');
                    }
                });
            });
        });

        function getSelectedPettyRows() {
            return $('.petty-row-select:checked').map(function() {
                const el = $(this);
                return {
                    prId: el.val(),
                    id: el.data('id'),
                    bankAcc: el.data('bank-acc'),
                    type: el.data('type'),
                    amount: el.data('amount'),
                    sup: el.data('sup'),
                    payee: el.data('payee')
                };
            }).get();
        }

        function confirmPettyRowsSequentially(rows, index, onDone) {
            if (index >= rows.length) {
                onDone(true);
                return;
            }
            confirmPettyVoucher(rows[index], function(ok) {
                if (ok) {
                    confirmPettyRowsSequentially(rows, index + 1, onDone);
                } else {
                    onDone(false);
                }
            });
        }

        // Bulk confirm selected
        $('#confirmPettySelectedBtn').on('click', function() {
            const rows = getSelectedPettyRows();
            if (rows.length === 0) return;
            const btn = $(this);

            Swal.fire({
                title: `Confirm ${rows.length} petty cash voucher(s)?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Confirm'
            }).then((result) => {
                if (!result.isConfirmed) return;

                btn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Processing...');
                confirmPettyRowsSequentially(rows, 0, function(ok) {
                    if (ok) {
                        Swal.fire({ icon: 'success', title: 'Confirmed!', showConfirmButton: false, timer: 1200 })
                            .then(() => location.reload());
                    } else {
                        btn.prop('disabled', false).html('<i class="bi bi-check2-all me-1"></i>Confirm Selected');
                    }
                });
            });
        });

        function updateConfirmPettyBtn() {
            $('#confirmPettySelectedBtn').prop('disabled', getSelectedPettyRows().length === 0);
        }

        $('#pettySelectAll').on('change', function() {
            $('.petty-row-select').prop('checked', this.checked);
            updateConfirmPettyBtn();
        });

        $(document).on('change', '.petty-row-select', function() {
            const total = $('.petty-row-select').length;
            const totalChecked = $('.petty-row-select:checked').length;
            $('#pettySelectAll').prop('checked', total === totalChecked);
            updateConfirmPettyBtn();
        });

        $('#pettyCashTable').DataTable({
            pageLength: 25,
            order: [[2, 'asc']],
            columnDefs: [{ orderable: false, targets: [0, 7] }]
        });

    </script>
</body>
</html>
