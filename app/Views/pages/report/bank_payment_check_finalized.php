<?= view('layouts/head') ?>

<style>
    .bpc-payee-header {
        background: #1e293b;
        color: #f1f5f9;
        cursor: pointer;
        user-select: none;
    }
    .bpc-payee-header:hover { background: #0f172a; }
    .bpc-payee-header td { padding: 0.6rem 1rem; }

    .bpc-voucher-header {
        background: #f1f5f9;
        border-left: 3px solid #64748b;
        cursor: pointer;
        user-select: none;
    }
    .bpc-voucher-header:hover { background: #e2e8f0; }
    .bpc-voucher-header td { padding: 0.45rem 1rem; color: #1e293b; }

    .bpc-tour-row td { padding: 0.35rem 1rem 0.35rem 2.5rem; font-size: 0.86rem; }
    .bpc-tour-row { background: #fff; border-left: 3px solid #e2e8f0; }
    .bpc-tour-row:hover { background: #f8fafc; }

    .bpc-payee-subtotal td { background: #f8fafc; border-top: 1px solid #e2e8f0; font-size: 0.82rem; padding: 0.35rem 1rem; color: #475569; font-weight: 600; }

    .chevron { transition: transform 0.18s; display: inline-block; }

    .badge-vnum { background: #334155; color: #fff; border-radius: 4px; padding: 2px 8px; font-size: 0.76rem; font-weight: 600; letter-spacing: 0.04em; }

    .bank-details-cell .acno { font-weight: 600; font-size: 0.81rem; color: #1e293b; }
    .bank-details-cell .bank-name { color: #64748b; font-size: 0.74rem; }
    .bank-details-cell .holder { color: #475569; font-size: 0.76rem; }

    @media print {
        .no-print, #layout-wrapper > *:not(.main-content) { display: none !important; }
        .main-content { margin: 0 !important; }
        .card { box-shadow: none !important; }
        .bpc-payee-header { background: #1e293b !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .bpc-voucher-header { background: #f1f5f9 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    }
</style>

<body class="bg-light">
    <div id="layout-wrapper">
        <?= view('layouts/header') ?>
        <?= view('layouts/sidebar') ?>

        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">

                    <!-- Page header -->
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <h4 class="text-primary fw-bold">Finalized Bank Payment Records</h4>
                        </div>
                        <div class="col-md-4 text-end">
                            <h6 class="text-secondary"><?= date('Y-m-d') ?></h6>
                            <a href="<?= base_url('report/bank_payment_check') ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-arrow-left-circle me-1"></i>Back to Bank Payment Check
                            </a>
                        </div>
                    </div>

                    <!-- Toolbar -->
                    <div class="card shadow-sm border-0 rounded mb-4 no-print">
                        <div class="card-body py-3">
                            <!-- Row 1: date filter + print -->
                            <form method="GET" action="" class="d-flex gap-2 align-items-end flex-wrap mb-3">
                                <div>
                                    <label class="form-label fw-semibold mb-1" style="font-size:0.8rem;">Bank Date From</label>
                                    <input type="date" name="from" class="form-control form-control-sm" value="<?= esc($filter_from) ?>">
                                </div>
                                <div>
                                    <label class="form-label fw-semibold mb-1" style="font-size:0.8rem;">Bank Date To</label>
                                    <input type="date" name="to" class="form-control form-control-sm" value="<?= esc($filter_to) ?>">
                                </div>
                                <div>
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        <i class="bi bi-search me-1"></i>Filter
                                    </button>
                                </div>
                                <div>
                                    <button type="button" class="btn btn-outline-success btn-sm" onclick="window.print()">
                                        <i class="bi bi-printer me-1"></i>Print
                                    </button>
                                </div>
                            </form>
                            <!-- Row 2: live search -->
                            <div class="row g-2 align-items-end">
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold mb-1" style="font-size:0.8rem;">Payee Name</label>
                                    <input type="text" id="srchPayee" class="form-control form-control-sm" placeholder="Search payee…">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-semibold mb-1" style="font-size:0.8rem;">Tour ID</label>
                                    <input type="text" id="srchTour" class="form-control form-control-sm" placeholder="e.g. AH 1234">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-semibold mb-1" style="font-size:0.8rem;">Voucher No</label>
                                    <input type="text" id="srchVnum" class="form-control form-control-sm" placeholder="e.g. 7447">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-semibold mb-1" style="font-size:0.8rem;">Date</label>
                                    <input type="date" id="srchDate" class="form-control form-control-sm">
                                </div>
                                <div class="col-auto">
                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="srchClear">
                                        <i class="bi bi-x-circle me-1"></i>Clear
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($payments)):
                        // ── Pre-group: Payee → Voucher → Tours ──────────────────────
                        $grouped = [];
                        foreach ($payments as $row) {
                            $payee = $row['payee'] ?? 'Unknown Payee';
                            $vnum  = $row['Vnum'];
                            if (!isset($grouped[$payee])) {
                                $grouped[$payee] = ['total' => 0, 'vouchers' => [], 'bank_dates' => []];
                            }
                            // collect unique bank dates for payee-level display
                            if (!empty($row['bank_date'])) {
                                $bd = date('Y-m-d', strtotime($row['bank_date']));
                                $grouped[$payee]['bank_dates'][$bd] = true;
                            }
                            if (!isset($grouped[$payee]['vouchers'][$vnum])) {
                                $grouped[$payee]['vouchers'][$vnum] = ['rows' => [], 'total' => (float)$row['voucher_total'], 'first_row' => $row];
                                $grouped[$payee]['total'] += (float)$row['voucher_total'];
                            }
                            $grouped[$payee]['vouchers'][$vnum]['rows'][] = $row;
                        }
                        $grandTotal = array_sum(array_map(fn($pg) => $pg['total'], $grouped));
                    ?>

                    <!-- Summary -->
                    <div class="row mb-3 no-print">
                        <div class="col-md-3">
                            <div class="card border-0 shadow-sm text-center py-3">
                                <div class="text-muted small">Total Vouchers</div>
                                <div class="fw-bold fs-4 text-primary"><?= array_sum(array_map(fn($pg) => count($pg['vouchers']), $grouped)) ?></div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-0 shadow-sm text-center py-3">
                                <div class="text-muted small">Total Amount (LKR)</div>
                                <div class="fw-bold fs-5 text-success"><?= number_format($grandTotal, 2) ?></div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-0 shadow-sm text-center py-3">
                                <div class="text-muted small">Period</div>
                                <div class="fw-bold fs-6 text-dark"><?= esc($filter_from) ?> &mdash; <?= esc($filter_to) ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Main table -->
                    <div class="card shadow-sm border-0 rounded">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table align-middle mb-0" id="finalizedTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Payee / Voucher / Tour</th>
                                            <th>Payee Bank Details</th>
                                            <th>Bank</th>
                                            <th class="text-end">Amount (LKR)</th>
                                            <th>Invoice (V Created) Date</th>
                                            <th>Bank Date</th>
                                            <th>Finalized By</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($grouped as $payee => $payeeData):
                                            $payeeId = 'payee-' . md5($payee);
                                        ?>

                                        <!-- ══ PAYEE HEADER ══ -->
                                        <tr class="bpc-payee-header" data-toggle-group="<?= $payeeId ?>" data-search-payee="<?= esc(strtolower($payee)) ?>">
                                            <td colspan="3">
                                                <span class="chevron me-2"><i class="bi bi-chevron-down"></i></span>
                                                <strong><?= esc($payee) ?></strong>
                                                <span class="ms-3 text-white-50" style="font-size:0.8rem;"><?= count($payeeData['vouchers']) ?> voucher(s)</span>
                                            </td>
                                            <td class="text-end fw-bold"><?= number_format($payeeData['total'], 2) ?></td>
                                            <td></td>
                                            <td class="fw-semibold" style="font-size:0.82rem; white-space:nowrap;">
                                                <!-- <?//php foreach (array_keys($payeeData['bank_dates']) as $bd): ?>
                                                    <span class="badge" style="background:rgba(255,255,255,0.2);font-size:0.75rem;"><?//= esc($bd) ?></span>
                                                <?//php endforeach; ?> -->
                                            </td>
                                            <td></td>
                                        </tr>

                                        <?php foreach ($payeeData['vouchers'] as $vnum => $voucherData):
                                            $voucherId = $payeeId . '-v-' . md5($vnum);
                                            $fr = $voucherData['first_row'];
                                            $hasMultipleTours = count($voucherData['rows']) > 1;
                                            $voucherTourIds = implode(' ', array_map(fn($r) => strtolower($r['manual_id'] ?: $r['tour_id']), $voucherData['rows']));
                                            $voucherDate = $fr['pay_date'] ? date('Y-m-d', strtotime($fr['pay_date'])) : '';
                                        ?>

                                        <!-- ── VOUCHER SUB-HEADER ── -->
                                        <tr class="bpc-voucher-header <?= $payeeId ?>"
                                            data-toggle-group="<?= $voucherId ?>"
                                            data-search-vnum="<?= esc(strtolower($vnum)) ?>"
                                            data-search-tour="<?= esc($voucherTourIds) ?>"
                                            data-search-date="<?= esc($voucherDate) ?>">
                                            <td>
                                                <span class="chevron me-2"><i class="bi bi-chevron-down"></i></span>
                                                <span class="badge-vnum"><?= esc($vnum) ?></span>
                                                <?php if ($hasMultipleTours): ?>
                                                    <span class="ms-2 text-primary" style="font-size:0.78rem;"><?= count($voucherData['rows']) ?> tour(s)</span>
                                                <?php else: ?>
                                                    <span class="ms-2 text-muted" style="font-size:0.78rem;"><?= esc($fr['manual_id'] ?: $fr['tour_id']) ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="bank-details-cell">
                                                <?php if (!empty($fr['sup_bank_acc'])): ?>
                                                    <div class="acno"><?= esc($fr['sup_bank_acc']) ?></div>
                                                    <?php if (!empty($fr['sup_bank_name'])): ?>
                                                        <div class="bank-name"><?= esc($fr['sup_bank_name']) ?><?= !empty($fr['sup_branch_name']) ? ' / ' . esc($fr['sup_branch_name']) : '' ?></div>
                                                    <?php endif; ?>
                                                    <?php if (!empty($fr['sup_acc_holder'])): ?>
                                                        <div class="holder"><?= esc($fr['sup_acc_holder']) ?></div>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">—</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= esc($fr['Bank']) ?></td>
                                            <td class="text-end fw-semibold" style="color:#334155;"><?= number_format($voucherData['total'], 2) ?></td>
                                            <td><?= $fr['pay_date'] ? date('Y-m-d', strtotime($fr['pay_date'])) : '-' ?></td>
                                            <td><?= $fr['bank_date'] ? date('Y-m-d', strtotime($fr['bank_date'])) : '-' ?></td>
                                            <td>
                                                <small class="text-muted"><?= esc($fr['finalized_user']) ?></small>
                                                <?php if (!empty($fr['finalized_date'])): ?>
                                                    <br><small class="text-muted"><?= date('Y-m-d H:i', strtotime($fr['finalized_date'])) ?></small>
                                                <?php endif; ?>
                                            </td>
                                        </tr>

                                        <!-- ·· TOUR ROWS ·· -->
                                        <?php foreach ($voucherData['rows'] as $row): ?>
                                        <tr class="bpc-tour-row <?= $voucherId ?>">
                                            <td></td>
                                            <td> <strong class="text-dark"><?= esc($row['manual_id'] ?: $row['tour_id']) ?></strong>
                                                <?php if (!empty($row['tour_name'])): ?>
                                                    <br><span class="text-muted" style="font-size:0.75rem;"><?= esc($row['tour_name']) ?></span>
                                                <?php endif; ?>
                                                <!-- <?//php if (!empty($row['vi_description'])): ?>
                                                    <br><span class="text-secondary" style="font-size:0.73rem;"><?//= esc($row['vi_description']) ?></span>
                                                <?//php endif; ?> -->
                                            </td>
                                            <td></td>
                                            <td class="text-end text-success fw-semibold"><?= number_format((float)$row['vi_amount'], 2) ?></td>
                                            <td><?= !empty($row['inv_date']) ? date('Y-m-d', strtotime($row['inv_date'])) : '-' ?></td>
                                            <td></td>
                                            <td></td>
                                        </tr>
                                        <?php endforeach; // tours ?>

                                        <?php endforeach; // vouchers ?>

                                        <!-- Payee subtotal -->
                                        <tr class="bpc-payee-subtotal <?= $payeeId ?>">
                                            <td colspan="3" class="text-end fst-italic">Total for <strong><?= esc($payee) ?></strong></td>
                                            <td class="text-end"><?= number_format($payeeData['total'], 2) ?></td>
                                            <td colspan="3"></td>
                                        </tr>

                                        <?php endforeach; // payees ?>
                                    </tbody>
                                    <tfoot class="table-light fw-semibold">
                                        <tr>
                                            <td colspan="3" class="text-end">Grand Total</td>
                                            <td class="text-end text-success"><?= number_format($grandTotal, 2) ?></td>
                                            <td colspan="3"></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                    <?php else: ?>
                    <div class="card shadow-sm border-0 rounded">
                        <div class="card-body text-center text-muted py-5">
                            <i class="bi bi-inbox" style="font-size:2rem;display:block;margin-bottom:0.5rem;opacity:0.4;"></i>
                            No finalized payments found for <?= esc($filter_from) ?> &mdash; <?= esc($filter_to) ?>
                        </div>
                    </div>
                    <?php endif; ?>

                </div>
            </div>
            <?= view('layouts/brand') ?>
        </div>
    </div>

    <?= view('layouts/inpage_footer') ?>
    <?= view('layouts/scripts') ?>

    <script>
    // ── Collapse/expand ───────────────────────────────────────────────────────
    $(document).on('click', '[data-toggle-group]', function(e) {
        if ($(e.target).is('input[type=checkbox]') || $(e.target).closest('input[type=checkbox]').length) return;
        const groupId = $(this).data('toggle-group');
        const $children = $('tr.' + groupId);
        const isHidden = $children.first().is(':hidden');
        $children.toggle(isHidden);
        $(this).find('.chevron i').toggleClass('bi-chevron-down bi-chevron-right');
    });

    // ── Live search ───────────────────────────────────────────────────────────
    function runSearch() {
        const payee = $('#srchPayee').val().trim().toLowerCase();
        const tour  = $('#srchTour').val().trim().toLowerCase();
        const vnum  = $('#srchVnum').val().trim().toLowerCase();
        const date  = $('#srchDate').val().trim();
        const hasFilter = payee || tour || vnum || date;

        if (!hasFilter) {
            $('tr.bpc-payee-header, tr.bpc-voucher-header, tr.bpc-tour-row, tr.bpc-payee-subtotal').show();
            return;
        }

        $('tr.bpc-payee-header').each(function() {
            const $payeeRow = $(this);
            const payeeVal  = $payeeRow.data('search-payee') || '';
            const payeeId   = $payeeRow.data('toggle-group');
            const payeeMatch = !payee || payeeVal.includes(payee);
            let anyVoucherVisible = false;

            $('tr.bpc-voucher-header.' + payeeId).each(function() {
                const $vRow   = $(this);
                const vnumVal = $vRow.data('search-vnum') || '';
                const tourVal = $vRow.data('search-tour') || '';
                const dateVal = $vRow.data('search-date') || '';
                const vId     = $vRow.data('toggle-group');

                const vnumMatch = !vnum || String(vnumVal).includes(vnum);
                const tourMatch = !tour || tourVal.includes(tour);
                const dateMatch = !date || dateVal === date;

                const visible = payeeMatch && vnumMatch && tourMatch && dateMatch;
                $vRow.toggle(visible);
                $('tr.' + vId).toggle(visible);
                if (visible) anyVoucherVisible = true;
            });

            $payeeRow.toggle(anyVoucherVisible);
            $('tr.bpc-payee-subtotal.' + payeeId).toggle(anyVoucherVisible);
        });
    }

    $('#srchPayee, #srchTour, #srchVnum').on('input', runSearch);
    $('#srchDate').on('change', runSearch);
    $('#srchClear').on('click', function() {
        $('#srchPayee, #srchTour, #srchVnum').val('');
        $('#srchDate').val('');
        runSearch();
    });
    </script>
</body>
</html>
