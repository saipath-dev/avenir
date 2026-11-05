<?php $hideApprovalActions = (int) (session()->get('group_id')) === 25; ?>
<?= view('layouts/head') ?>

<style>
    .bpc-payee-header {
        background: #3b82f6;
        color: #fff;
        cursor: pointer;
        user-select: none;
    }

    .bpc-payee-header:hover {
        background: #2563eb;
    }

    .bpc-payee-header td {
        padding: 0.6rem 1rem;
    }

    .bpc-voucher-header {
        background: #eff6ff;
        border-left: 3px solid #93c5fd;
        cursor: pointer;
        user-select: none;
    }

    .bpc-voucher-header:hover {
        background: #dbeafe;
    }

    .bpc-voucher-header td {
        padding: 0.45rem 1rem;
        color: #1e3a8a;
    }

    .bpc-tour-row td {
        padding: 0.35rem 1rem 0.35rem 2.5rem;
        font-size: 0.86rem;
    }

    .bpc-tour-row {
        background: #fff;
        border-left: 3px solid #bfdbfe;
    }

    .bpc-tour-row:hover {
        background: #f0f9ff;
    }

    .bpc-payee-subtotal td {
        background: #eff6ff;
        border-top: 1px solid #bfdbfe;
        font-size: 0.82rem;
        padding: 0.35rem 1rem;
        color: #1e40af;
        font-weight: 600;
    }

    .chevron {
        transition: transform 0.18s;
        display: inline-block;
    }

    .badge-vnum {
        background: #1d4ed8;
        color: #fff;
        border-radius: 4px;
        padding: 2px 8px;
        font-size: 0.76rem;
        font-weight: 600;
        letter-spacing: 0.04em;
    }

    .badge-finalized {
        background: #15803d;
        color: #fff;
        border-radius: 4px;
        padding: 2px 8px;
        font-size: 0.74rem;
    }

    .bank-details-cell .acno {
        font-weight: 600;
        font-size: 0.81rem;
        color: #1e3a8a;
    }

    .bank-details-cell .bank-name {
        color: #64748b;
        font-size: 0.74rem;
    }

    .bank-details-cell .holder {
        color: #374151;
        font-size: 0.76rem;
    }

    @media print {

        .no-print,
        #layout-wrapper>*:not(.main-content) {
            display: none !important;
        }

        .main-content {
            margin: 0 !important;
        }

        .card {
            box-shadow: none !important;
        }

        .bpc-payee-header {
            background: #3b82f6 !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .bpc-voucher-header {
            background: #eff6ff !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
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
                            <h4 class="text-primary fw-bold">Bank Payment Check</h4>
                        </div>
                        <div class="col-md-4 text-end">
                            <h6 class="text-secondary"><?= date('Y-m-d') ?></h6>
                            <a href="<?= base_url('/dashboard2/tiles') ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-arrow-left-circle me-1"></i>Back to Dashboard
                            </a>
                        </div>
                    </div>

                    <!-- Toolbar -->
                    <div class="card shadow-sm border-0 rounded mb-4 no-print">
                        <div class="card-body py-3">
                            <!-- Row 1: action buttons -->
                            <div class="d-flex gap-2 mb-3">
                                <a href="<?= base_url('report/bank_payment_check/finalized_records') ?>" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-card-checklist me-1"></i>Finalized Records
                                </a>
                                <button type="button" class="btn btn-outline-success btn-sm" onclick="window.print()">
                                    <i class="bi bi-printer me-1"></i>Print
                                </button>
                                <?php if (!$hideApprovalActions && empty($tpd_closed)): /* tf-mfguard-r1/r2: only while the Tour Payment Desk is absent */ ?>
                                    <button type="button" class="btn btn-success btn-sm" id="finalizeSelectedBtn" disabled>
                                        <i class="bi bi-check2-all me-1"></i>Finalize Selected
                                    </button>
                                <?php elseif (!empty($tpd_closed)): ?>
                                    <a href="<?= base_url('tour/payment_desk') ?>" class="btn btn-outline-secondary btn-sm" title="With the Tour Payment Desk installed, payments are completed there: bank initiation by the accountant, then Paid by the bank approver">
                                        <i class="bi bi-box-arrow-up-right me-1"></i>Payments are completed on the Payment Desk
                                    </a>
                                    <?php if (($tpd_desk_state ?? '') === 'unreadable'): ?><span class="badge bg-danger ms-1" title="The Payment Desk's status could not be read; finalizing is closed">Payment Desk status unreadable</span><?php endif; ?>
                                <?php endif; ?>
                            </div>
                            <!-- Row 2: search inputs -->
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
                                $grouped[$payee] = ['rows' => [], 'total' => 0, 'vouchers' => []];
                            }
                            if (!isset($grouped[$payee]['vouchers'][$vnum])) {
                                // voucher_total is the same for every v_inv row of this voucher — store once
                                $grouped[$payee]['vouchers'][$vnum] = ['rows' => [], 'total' => (float)$row['voucher_total'], 'first_row' => $row];
                                $grouped[$payee]['total'] += (float)$row['voucher_total'];
                            }
                            $grouped[$payee]['vouchers'][$vnum]['rows'][] = $row;
                        }
                        // Grand total = sum of unique voucher totals
                        $grandTotal = array_sum(array_map(fn($pg) => $pg['total'], $grouped));
                    ?>

                        <!-- Main table -->
                        <div class="card shadow-sm border-0 rounded">
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table align-middle mb-0" id="paymentTable">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width:36px;"><input type="checkbox" id="selectAll" title="Select all"></th>
                                                <th>Payee / Voucher / Tour</th>
                                                <th>Payee Bank Details</th>
                                                <th>Type</th>
                                                <th>Bank</th>
                                                <th class="text-end">Amount (LKR)</th>
                                                <th>Date</th>
                                                <th class="no-print">Finalize</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($grouped as $payee => $payeeData):
                                                $payeeId = 'payee-' . md5($payee);
                                            ?>

                                                <!-- ══ PAYEE HEADER ══ -->
                                                <tr class="bpc-payee-header" data-toggle-group="<?= $payeeId ?>" data-search-payee="<?= esc(strtolower($payee)) ?>">
                                                    <td>
                                                        <input type="checkbox" class="payee-select-all no-print"
                                                            data-payee="<?= esc($payee) ?>"
                                                            title="Select all for <?= esc($payee) ?>"
                                                            onclick="event.stopPropagation();">
                                                    </td>
                                                    <td colspan="5">
                                                        <span class="chevron me-2"><i class="bi bi-chevron-down"></i></span>
                                                        <strong><?= esc($payee) ?></strong>
                                                        <?php $firstVrow = reset($payeeData['vouchers'])['first_row'];
                                                        if (!empty($firstVrow['hotel_code'])): ?>
                                                            <span class="badge bg-info text-dark ms-2"><?= esc($firstVrow['hotel_code']) ?></span>
                                                        <?php endif; ?>
                                                        <span class="ms-3 text-white-50" style="font-size:0.8rem;"><?= count($payeeData['vouchers']) ?> voucher(s)</span>
                                                    </td>
                                                    <td class="text-end fw-bold" style="font-size:0.95rem;">
                                                        <?= number_format($payeeData['total'], 2) ?>
                                                    </td>
                                                    <td></td>
                                                </tr>

                                                <?php foreach ($payeeData['vouchers'] as $vnum => $voucherData):
                                                    $voucherId = $payeeId . '-v-' . md5($vnum);
                                                    $fr = $voucherData['first_row'];   // representative row for shared fields
                                                    $hasMultipleTours = count($voucherData['rows']) > 1;
                                                ?>

                                                    <!-- ── VOUCHER SUB-HEADER ── -->
                                                    <?php
                                                    $voucherTourIds = implode(' ', array_map(fn($r) => strtolower($r['manual_id'] ?: $r['tour_id']), $voucherData['rows']));
                                                    $voucherDate = $fr['pay_date'] ? date('Y-m-d', strtotime($fr['pay_date'])) : '';
                                                    ?>
                                                    <tr class="bpc-voucher-header <?= $payeeId ?>"
                                                        data-toggle-group="<?= $voucherId ?>"
                                                        data-search-vnum="<?= esc(strtolower($vnum)) ?>"
                                                        data-search-tour="<?= esc($voucherTourIds) ?>"
                                                        data-search-date="<?= esc($voucherDate) ?>">
                                                        <td>
                                                            <?php if ($fr['finalized'] != 1): ?>
                                                                <input type="checkbox" class="row-select no-print"
                                                                    data-payee="<?= esc($payee) ?>"
                                                                    value="<?= esc($vnum) ?>"
                                                                    data-status="<?= (int)$fr['status'] ?>"
                                                                    data-id="<?= esc($fr['id']) ?>"
                                                                    data-bank-acc="<?= esc($fr['Bank_acc']) ?>"
                                                                    data-type="<?= esc($fr['v_typ']) ?>"
                                                                    data-amount="<?= esc($voucherData['total']) ?>"
                                                                    data-sup="<?= esc($fr['sup']) ?>"
                                                                    onclick="event.stopPropagation();">
                                                            <?php endif; ?>
                                                        </td>
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
                                                            <?php /* tf-bankreg-r1: the registered account carried by this voucher (copied when selected), never retyped; the master's columns only when no account was carried */
                                                            $pa = $tpd_pay_accounts[(string) $vnum] ?? null; $rv = $pa['review'] ?? null; $tpdB0 = $tpd_batches[(string) $vnum] ?? null; ?>
                                                            <?php if ($pa): ?>
                                                                <div class="holder" style="font-weight:700;color:#1f2937;"><?= esc($pa['holder_name'] ?: '—') ?> <small class="text-muted fw-normal">account holder</small></div>
                                                                <div class="bank-name"><?= esc($pa['bank_name'] ?: '—') ?><?= $pa['branch'] ? ' / ' . esc($pa['branch']) : '' ?></div>
                                                                <div class="acno"><?= esc($pa['account_no'] ?: '—') ?><?= $pa['currency'] ? ' <span class="badge bg-light text-dark border">' . esc($pa['currency']) . ' account</span>' : '' ?></div>
                                                                <div class="small text-muted">Payee <?= esc($pa['supplier_name'] ?: $payee) ?> · <?= $tpdB0 ? 'batch ' . esc($tpdB0['batch_no']) . ' · ' : '' ?>PV <?= (int) $vnum ?> · selected by <?= esc($pa['selected_by']) ?> <?= esc(substr((string) $pa['selected_at'], 0, 16)) ?></div>
                                                                <?php if ($rv): ?>
                                                                    <?php $cls = $rv['state'] === 'ok' ? ($rv['label'] === 'Verified' ? 'success' : 'secondary') : ($rv['state'] === 'reviewed' ? 'info' : 'danger'); ?>
                                                                    <div class="small mt-1"><span class="badge bg-<?= $cls ?>-subtle text-<?= $cls ?> border border-<?= $cls ?>-subtle"><?= esc($rv['label']) ?></span><?= $rv['detail'] ? '<div class="text-' . $cls . '" style="font-size:.74rem;">' . esc($rv['detail']) . '</div>' : '' ?></div>
                                                                    <?php if (!empty($rv['blocking']) && in_array($rv['state'], ['changed', 'inactive'], true) && !empty($tpd_can_review_account)): ?>
                                                                        <div class="no-print mt-1"><button type="button" class="btn btn-sm btn-outline-danger tf-acc-review" data-vnum="<?= (int) $vnum ?>" data-decision="keep">Keep approved account</button> <button type="button" class="btn btn-sm btn-outline-primary tf-acc-review" data-vnum="<?= (int) $vnum ?>" data-decision="update">Take registry details</button></div>
                                                                    <?php endif; ?>
                                                                <?php endif; ?>
                                                            <?php elseif (!empty($fr['sup_bank_acc'])): ?>
                                                                <div class="acno"><?= esc($fr['sup_bank_acc']) ?></div>
                                                                <?php if (!empty($fr['sup_bank_name'])): ?>
                                                                    <div class="bank-name"><?= esc($fr['sup_bank_name']) ?><?= !empty($fr['sup_branch_name']) ? ' / ' . esc($fr['sup_branch_name']) : '' ?></div>
                                                                <?php endif; ?>
                                                                <?php if (!empty($fr['sup_acc_holder'])): ?>
                                                                    <div class="holder"><?= esc($fr['sup_acc_holder']) ?></div>
                                                                <?php endif; ?>
                                                                <?php if (!empty($tpd_registry)): ?><div class="small text-danger mt-1"><i class="bi bi-exclamation-triangle me-1"></i>No registered account carried with this voucher (supplier master shown). Register and select it before the transfer.</div><?php endif; ?>
                                                            <?php else: ?>
                                                                <span class="text-muted">—</span><?php if (!empty($tpd_registry)): ?><div class="small text-danger mt-1"><i class="bi bi-exclamation-triangle me-1"></i>No bank account on record — register it in the Supplier Bank Account Registry.</div><?php endif; ?>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td colspan="2"><?= esc($fr['Bank']) ?></td>
                                                        <td class="text-end fw-semibold" style="color:#4f46e5;">
                                                            <?= number_format($voucherData['total'], 2) ?>
                                                        </td>
                                                        <td><?= $fr['pay_date'] ? date('Y-m-d', strtotime($fr['pay_date'])) : '-' ?></td>
                                                        <td class="no-print">
                                                            <?php if ($fr['finalized'] == 1): ?>
                                                                <span class="badge-finalized"><i class="bi bi-check2-circle me-1"></i>Finalized</span>
                                                                <br><small class="text-muted"><?= esc($fr['finalized_user']) ?></small>
                                                                <br><small class="text-muted"><?= date('Y-m-d H:i', strtotime($fr['finalized_date'])) ?></small>
                                                            <?php else: ?>
                                                                <?php /* tf-paydesk-r1: the accountant's initiation, when recorded, and who may click Paid */ $tpdI = $tpd_initiations[(string) $vnum] ?? null; ?>
                                                                <?php /* tf-paydesk-r2: a supplier batch voucher is initiated and paid on its batch page (the payment-date rate) */ $tpdB = $tpd_batches[(string) $vnum] ?? null; ?>
                                                                <?php if ($tpdB && !empty($tpdB['parent_id'])): /* tf-paydesk-r5: one of the vouchers of a bulk transfer */ ?>
                                                                    <div class="small mb-1"><a href="<?= base_url('tour/tour_payment_dashboard/bulk/' . (int) $tpdB['parent_id']) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-collection me-1"></i>Payment Voucher No. <?= (int) $vnum ?> · bulk batch <?= esc($tpdB['parent_no']) ?> (<?= (int) $tpdB['parent_count'] ?> vouchers, one transfer) — <?= $tpdI ? 'final approval (Paid) for the whole batch' : 'the whole transfer is initiated on the bulk page' ?></a></div>
                                                                <?php elseif ($tpdB): ?>
                                                                    <div class="small mb-1"><a href="<?= base_url('tour/tour_payment_dashboard/batch/' . (int) $tpdB['id']) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-collection me-1"></i>Payment Voucher No. <?= (int) $vnum ?> · batch <?= esc($tpdB['batch_no']) ?> — <?= $tpdI ? 'final approval (Paid)' : 'bank payment page' ?></a><?= (int) $tpdB['item_count'] > 1 ? ' <small class="text-muted">' . (int) $tpdB['item_count'] . ' payments · ' . (int) $tpdB['tour_count'] . ' tour(s)</small>' : '' ?></div>
                                                                <?php endif; ?>
                                                                <?php if ($tpdI): ?>
                                                                    <div class="small mb-1" style="color:#0f766e;"><i class="bi bi-bank me-1"></i>Bank initiated <?= esc(date('Y-m-d H:i', strtotime($tpdI['initiated_at']))) ?> by <?= esc($tpdI['initiated_by']) ?><?= $tpdI['label'] ? ' · ' . esc($tpdI['label']) : '' ?><?= $tpdI['bank_ref'] ? ' · ref ' . esc($tpdI['bank_ref']) : ($tpdI['batch_ref'] ? ' · batch ' . esc($tpdI['batch_ref']) : '') ?></div>
                                                                <?php elseif (!empty($tpd_ready)): ?>
                                                                    <div class="small text-muted mb-1">Bank initiation not yet recorded (<?= $tpdB ? 'the batch page above' : 'Tour Payment Dashboard → Record Bank Initiation' ?>)</div>
                                                                <?php endif; ?>
                                                                <?php if (!empty($tpd_closed) && !$tpdB): /* tf-mfguard-r1/r2: with the desk installed, Paid is completed on the Payment Desk, not here */ ?>
                                                                    <div class="small mb-1"><a href="<?= base_url('tour/payment_desk') . '?vnum=' . (int) $vnum ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-box-arrow-up-right me-1"></i>Complete on the Payment Desk</a> <span class="text-muted">bank initiation, then Paid by the bank approver</span></div>
                                                                <?php endif; ?>
                                                                <?php if (!$hideApprovalActions && empty($tpd_closed) && !$tpdB): ?>
                                                                    <button class="btn btn-sm btn-outline-success finalize-btn"
                                                                        data-pr-id="<?= esc($vnum) ?>"
                                                                        data-status="<?= (int)$fr['status'] ?>"
                                                                        data-id="<?= esc($fr['id']) ?>"
                                                                        data-bank-acc="<?= esc($fr['Bank_acc']) ?>"
                                                                        data-type="<?= esc($fr['v_typ']) ?>"
                                                                        data-amount="<?= esc($voucherData['total']) ?>"
                                                                        data-sup="<?= esc($fr['sup']) ?>"
                                                                        data-payee="<?= esc($payee) ?>">
                                                                        <i class="bi bi-check2-circle me-1"></i>Mark Finalized
                                                                    </button>
                                                                <?php endif; ?>
                                                                <?php if (!$hideApprovalActions): ?>
                                                                    <button class="btn btn-sm btn-outline-danger cancel-voucher-btn ms-1"
                                                                        data-pr-id="<?= esc($vnum) ?>"
                                                                        data-id="<?= esc($fr['id']) ?>"
                                                                        data-payee="<?= esc($payee) ?>">
                                                                        <i class="bi bi-x-circle me-1"></i>Cancel
                                                                    </button>
                                                                <?php endif; ?>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>

                                                    <!-- ·· TOUR ROWS — one per v_inv line ·· -->
                                                    <?php foreach ($voucherData['rows'] as $row): ?>
                                                        <tr class="bpc-tour-row <?= $voucherId ?>">
                                                            <td></td>
                                                            <td>


                                                            </td>
                                                            <td><strong class="text-dark"><?= esc($row['manual_id'] ?: $row['tour_id']) ?></strong>
                                                                <?php if (!empty($row['tour_name'])): ?>
                                                                    <br><span class="text-muted" style="font-size:0.75rem;"><?= esc($row['tour_name']) ?></span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>

                                                            </td>
                                                            <td></td>
                                                            <td class="text-end text-success fw-semibold">
                                                                <?= number_format((float)$row['vi_amount'], 2) ?>
                                                            </td>
                                                            <td><?= !empty($row['inv_date']) ? date('Y-m-d', strtotime($row['inv_date'])) : '-' ?></td>
                                                            <td></td>
                                                        </tr>
                                                    <?php endforeach; // tours 
                                                    ?>

                                                <?php endforeach; // vouchers 
                                                ?>

                                                <!-- Payee subtotal -->
                                                <tr class="bpc-payee-subtotal <?= $payeeId ?>">
                                                    <td></td>
                                                    <td colspan="4" class="text-end fst-italic">
                                                        Total for <strong><?= esc($payee) ?></strong>
                                                    </td>
                                                    <td class="text-end"><?= number_format($payeeData['total'], 2) ?></td>
                                                    <td colspan="2"></td>
                                                </tr>

                                            <?php endforeach; // payees 
                                            ?>
                                        </tbody>
                                        <tfoot class="table-light fw-semibold">
                                            <tr>
                                                <td></td>
                                                <td colspan="4" class="text-end">Grand Total</td>
                                                <td class="text-end text-success"><?= number_format($grandTotal, 2) ?></td>
                                                <td colspan="2"></td>
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
                                No pending payments found.
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Petty Cash Voucher Confirmation (unchanged) -->
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
                                                <th><input type="checkbox" id="pettySelectAll" title="Select all"></th>
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

    <!-- Bank Date Modal (unchanged) -->
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
                    <label class="form-label fw-semibold mt-3">Remark <span class="text-muted fw-normal">(optional)</span></label>
                    <textarea id="finalizeRemarkInput" class="form-control" rows="2" placeholder="Optional remark…"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success btn-sm" id="confirmFinalizeBtn">Confirm</button>
                </div>
            </div>
        </div>
    </div>

    <?= view('layouts/inpage_footer') ?>
    <?= view('layouts/scripts') ?>
    <script src="<?= base_url() ?>assets/bundles/datatablescripts.bundle.js"></script>

    <script>
        // ── Search / filter ───────────────────────────────────────────────────────
        function runSearch() {
            const payee = $('#srchPayee').val().trim().toLowerCase();
            const tour = $('#srchTour').val().trim().toLowerCase();
            const vnum = $('#srchVnum').val().trim().toLowerCase();
            const date = $('#srchDate').val().trim();
            const hasFilter = payee || tour || vnum || date;

            if (!hasFilter) {
                // Show everything, restore collapsed state
                $('tr.bpc-payee-header, tr.bpc-voucher-header, tr.bpc-tour-row, tr.bpc-payee-subtotal').show();
                return;
            }

            // Process payee by payee
            $('tr.bpc-payee-header').each(function() {
                const $payeeRow = $(this);
                const payeeVal = $payeeRow.data('search-payee') || '';
                const payeeId = $payeeRow.data('toggle-group');

                // Check if payee name matches (if filter set)
                const payeeMatch = !payee || payeeVal.includes(payee);

                let anyVoucherVisible = false;

                // Check each voucher under this payee
                $('tr.bpc-voucher-header.' + payeeId).each(function() {
                    const $vRow = $(this);
                    const vnumVal = $vRow.data('search-vnum') || '';
                    const tourVal = $vRow.data('search-tour') || '';
                    const dateVal = $vRow.data('search-date') || '';
                    const vId = $vRow.data('toggle-group');

                    const vnumMatch = !vnum || String(vnumVal).includes(vnum);
                    const tourMatch = !tour || tourVal.includes(tour);
                    const dateMatch = !date || dateVal === date;

                    const voucherVisible = payeeMatch && vnumMatch && tourMatch && dateMatch;

                    if (voucherVisible) {
                        $vRow.show();
                        $('tr.' + vId).show(); // show its tour rows
                        anyVoucherVisible = true;
                    } else {
                        $vRow.hide();
                        $('tr.' + vId).hide();
                    }
                });

                // Show payee header + subtotal only if at least one voucher matches
                const payeeVisible = anyVoucherVisible;
                $payeeRow.toggle(payeeVisible);
                // subtotal row has same payeeId class
                $('tr.bpc-payee-subtotal.' + payeeId).toggle(payeeVisible);
            });
        }

        $('#srchPayee, #srchTour, #srchVnum').on('input', runSearch);
        $('#srchDate').on('change', runSearch);
        $('#srchClear').on('click', function() {
            $('#srchPayee, #srchTour, #srchVnum').val('');
            $('#srchDate').val('');
            runSearch();
        });

        // ── Collapse/expand for payee and voucher groups ──────────────────────────
        // All child rows start visible. Click header to toggle its children.
        $(document).on('click', '[data-toggle-group]', function(e) {
            // Don't collapse when clicking checkbox
            if ($(e.target).is('input[type=checkbox]') || $(e.target).closest('input[type=checkbox]').length) return;

            const groupId = $(this).data('toggle-group');
            const $children = $('tr.' + groupId);
            const isHidden = $children.first().is(':hidden');

            $children.toggle(isHidden);
            // Rotate chevron
            $(this).find('.chevron').toggleClass('rotated');
            if (isHidden) {
                $(this).find('.chevron i').removeClass('bi-chevron-right').addClass('bi-chevron-down');
            } else {
                $(this).find('.chevron i').removeClass('bi-chevron-down').addClass('bi-chevron-right');
            }
        });

        // ── Finalize logic (UNCHANGED) ────────────────────────────────────────────
        let pendingFinalize = null;
        // tf-mfguard-r1/r2: with the Tour Payment Desk installed (or its status unreadable) the page offers no finalize action; if one is
        // reached anyway (a page kept open from before), nothing is authorised or finalized here — the user is sent to the Payment Desk
        const TPD_DESK_INSTALLED = <?= !empty($tpd_closed) ? 'true' : 'false' ?>;
        // tf-paydesk-r7e: this page's security token, sent with Mark Finalized / Finalize Selected; a refused request (HTTP 403)
        // shows the server's reason and gives the button back
        const TF_CSRF = { '<?= csrf_token() ?>': '<?= csrf_hash() ?>' };
        function tfRefused(x, restore) {
            Swal.fire({ icon: 'error', title: 'Not done', text: (x && x.responseJSON && x.responseJSON.message) || 'Something went wrong. Please try again.' });
            if (restore) restore();
        }

        function deskOnly() {
            if (!TPD_DESK_INSTALLED) return false;
            Swal.fire({ icon: 'info', title: 'Complete on the Payment Desk', text: 'Mark Finalized is closed on this page because the Tour Payment Desk is installed. The accountant records the bank initiation, then the bank approver marks it Paid on the Payment Desk. Nothing was changed.' });
            return true;
        }

        function openBankDateModal(mode, data) {
            if (deskOnly()) return;
            pendingFinalize = {
                mode,
                ...data
            };
            $('#bankDateError').addClass('d-none');
            $('#bankDateInput').val('<?= date('Y-m-d') ?>');
            $('#finalizeRemarkInput').val('');
            $('#bankDateModal').modal('show');
        }

        $('#confirmFinalizeBtn').on('click', function() {
            const bankDate = $('#bankDateInput').val();
            const remark = $('#finalizeRemarkInput').val().trim();
            if (!bankDate) {
                $('#bankDateError').removeClass('d-none');
                return;
            }
            $('#bankDateModal').modal('hide');
            if (pendingFinalize.mode === 'single') {
                doSingleFinalize(pendingFinalize.prId, bankDate, remark);
            } else {
                doBulkFinalize(pendingFinalize.vnums, bankDate, pendingFinalize.rows, remark);
            }
        });

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
            $.post('<?= base_url('accounts/voucher_auth/update') ?>', { '<?= csrf_token() ?>': '<?= csrf_hash() ?>',   // tf-paydesk-r7f: the page's security token
                update: 'data',
                id: pending.id,
                status: 1,
                bank_acc: pending.bankAcc,
                type: pending.type,
                amount: pending.amount,
                payee: pending.payee,
                voucher_no: pending.prId,
                sup: pending.sup,
                remark: pending.remark || '',
            }, function(data) {
                if (data.status === 200) {
                    callback(true);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Failed to approve voucher'
                    });
                    callback(false);
                }
            }, 'json').fail(function(x) {   // tf-paydesk-r7f: the server's reason
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: (x && x.responseJSON && x.responseJSON.message) || 'Failed to approve voucher'
                });
                callback(false);
            });
        }

        // ── Cancel (reject) voucher ────────────────────────────────────────────────
        function rejectVoucher(id, prId, remark, callback) {
            $.post('<?= base_url('accounts/voucher_auth/update') ?>', { '<?= csrf_token() ?>': '<?= csrf_hash() ?>',   // tf-paydesk-r7f: the page's security token
                update: 'data',
                id: id,
                status: 2,
                voucher_no: prId,
                remark: remark
            }, function(data) {
                if (data.status === 200) {
                    callback(true);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Failed to cancel voucher'
                    });
                    callback(false);
                }
            }, 'json').fail(function(x) {   // tf-paydesk-r7f: the server's reason
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: (x && x.responseJSON && x.responseJSON.message) || 'Failed to cancel voucher'
                });
                callback(false);
            });
        }

        $(document).on('click', '.cancel-voucher-btn', function() {
            const btn = $(this);
            const id = btn.data('id');
            const prId = btn.data('pr-id');

            Swal.fire({
                title: `Cancel voucher ${prId}?`,
                html: '<div class="text-start"><label class="form-label fw-semibold mb-1">Remark <span class="text-danger">*</span></label>' +
                    '<textarea id="cancelRemarkInput" class="form-control" rows="3" placeholder="Reason for cancelling this voucher…"></textarea>' +
                    '<div id="cancelRemarkError" class="text-danger small mt-1 d-none">A remark is required to cancel a voucher.</div></div>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Cancel Voucher',
                focusConfirm: false,
                preConfirm: () => {
                    const remark = document.getElementById('cancelRemarkInput').value.trim();
                    if (!remark) {
                        document.getElementById('cancelRemarkError').classList.remove('d-none');
                        return false;
                    }
                    return remark;
                }
            }).then((result) => {
                if (!result.isConfirmed) return;
                const remark = result.value;
                btn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Processing...');
                rejectVoucher(id, prId, remark, function(ok) {
                    if (ok) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Voucher Cancelled',
                            showConfirmButton: false,
                            timer: 1200
                        }).then(() => location.reload());
                    } else {
                        btn.prop('disabled', false).html('<i class="bi bi-x-circle me-1"></i>Cancel');
                    }
                });
            });
        });

        function doSingleFinalize(prId, bankDate, remark) {
            if (deskOnly()) return;   // tf-mfguard-r1
            const btn = $(`.finalize-btn[data-pr-id="${prId}"]`);
            btn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Processing...');

            const finalizeAfterApprove = function() {
                $.post('<?= base_url('report/bank_payment_check/mark_finalized') ?>', {
                    pr_id: prId,
                    bank_date: bankDate,
                    ...TF_CSRF
                }, function(data) {
                    if (data.status === 200) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Finalized!',
                            showConfirmButton: false,
                            timer: 1200
                        }).then(() => location.reload());
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message
                        });
                        btn.prop('disabled', false).html('<i class="bi bi-check2-circle me-1"></i>Mark Finalized');
                    }
                }, 'json').fail(x => tfRefused(x, () => btn.prop('disabled', false).html('<i class="bi bi-check2-circle me-1"></i>Mark Finalized')));
            };

            if (String(pendingFinalize.status) === '0') {
                approveVoucher({
                    ...pendingFinalize,
                    remark
                }, function(ok) {
                    if (ok) finalizeAfterApprove();
                    else btn.prop('disabled', false).html('<i class="bi bi-check2-circle me-1"></i>Mark Finalized');
                });
                return;
            }

            $.post('<?= base_url('report/bank_payment_check/mark_finalized') ?>', {
                pr_id: prId,
                bank_date: bankDate,
                ...TF_CSRF
            }, function(data) {
                if (data.status === 200) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Finalized!',
                        showConfirmButton: false,
                        timer: 1200
                    }).then(() => location.reload());
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message
                    });
                    btn.prop('disabled', false).html('<i class="bi bi-check2-circle me-1"></i>Mark Finalized');
                }
            }, 'json').fail(x => tfRefused(x, () => btn.prop('disabled', false).html('<i class="bi bi-check2-circle me-1"></i>Mark Finalized')));
        }

        $('#finalizeSelectedBtn').on('click', function() {
            const vnums = getSelectedVnums();
            if (vnums.length === 0) return;
            openBankDateModal('bulk', {
                vnums,
                rows: getSelectedRows()
            });
        });

        function approveVouchersSequentially(rows, index, remark, onDone) {
            if (index >= rows.length) {
                onDone(true);
                return;
            }
            const row = rows[index];
            if (String(row.status) !== '0') {
                approveVouchersSequentially(rows, index + 1, remark, onDone);
                return;
            }
            approveVoucher({
                id: row.id,
                bankAcc: row.bankAcc,
                type: row.type,
                amount: row.amount,
                payee: row.payee,
                prId: row.prId,
                sup: row.sup,
                remark
            }, function(ok) {
                if (ok) approveVouchersSequentially(rows, index + 1, remark, onDone);
                else onDone(false);
            });
        }

        function doBulkFinalize(vnums, bankDate, rows, remark) {
            if (deskOnly()) return;   // tf-mfguard-r1
            $('#finalizeSelectedBtn').prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Processing...');
            const finalizeSelected = function() {
                $.post('<?= base_url('report/bank_payment_check/bulk_finalize') ?>', {
                    vnums: vnums,
                    bank_date: bankDate,
                    ...TF_CSRF
                }, function(data) {
                    if (data.status === 200) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Done!',
                            text: data.message,
                            showConfirmButton: false,
                            timer: 1500
                        }).then(() => location.reload());
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message
                        });
                        $('#finalizeSelectedBtn').prop('disabled', false).html('<i class="bi bi-check2-all me-1"></i>Finalize Selected');
                    }
                }, 'json').fail(x => tfRefused(x, () => $('#finalizeSelectedBtn').prop('disabled', false).html('<i class="bi bi-check2-all me-1"></i>Finalize Selected')));
            };
            approveVouchersSequentially(rows || [], 0, remark, function(ok) {
                if (ok) finalizeSelected();
                else $('#finalizeSelectedBtn').prop('disabled', false).html('<i class="bi bi-check2-all me-1"></i>Finalize Selected');
            });
        }

        function getSelectedVnums() {
            return $('.row-select:checked').map(function() {
                return $(this).val();
            }).get();
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

        $('#selectAll').on('change', function() {
            $('.row-select, .payee-select-all').prop('checked', this.checked);
            updateFinalizeBtn();
        });

        $(document).on('change', '.payee-select-all', function() {
            const payee = $(this).data('payee');
            $(`.row-select[data-payee="${payee}"]`).prop('checked', this.checked);
            updateFinalizeBtn();
        });

        $(document).on('change', '.row-select', function() {
            const payee = $(this).data('payee');
            const allInPayee = $(`.row-select[data-payee="${payee}"]`).length;
            const checkedInPayee = $(`.row-select[data-payee="${payee}"]:checked`).length;
            $(`.payee-select-all[data-payee="${payee}"]`).prop('checked', allInPayee === checkedInPayee);
            const total = $('.row-select').length;
            const totalChecked = $('.row-select:checked').length;
            $('#selectAll').prop('checked', total === totalChecked);
            updateFinalizeBtn();
        });

        // ── Petty Cash (UNCHANGED) ────────────────────────────────────────────────
        function confirmPettyVoucher(row, onDone) {
            approveVoucher(row, onDone);
        }

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
                    confirmButtonColor: '#78b848',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, Confirm'
                })
                .then((result) => {
                    if (!result.isConfirmed) return;
                    btn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Processing...');
                    confirmPettyVoucher(row, function(ok) {
                        if (ok) Swal.fire({
                            icon: 'success',
                            title: 'Confirmed!',
                            showConfirmButton: false,
                            timer: 1200
                        }).then(() => location.reload());
                        else btn.prop('disabled', false).html('<i class="bi bi-check2-circle me-1"></i>Confirm');
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
                if (ok) confirmPettyRowsSequentially(rows, index + 1, onDone);
                else onDone(false);
            });
        }

        $('#confirmPettySelectedBtn').on('click', function() {
            const rows = getSelectedPettyRows();
            if (rows.length === 0) return;
            const btn = $(this);
            Swal.fire({
                    title: `Confirm ${rows.length} petty cash voucher(s)?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#78b848',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, Confirm'
                })
                .then((result) => {
                    if (!result.isConfirmed) return;
                    btn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Processing...');
                    confirmPettyRowsSequentially(rows, 0, function(ok) {
                        if (ok) Swal.fire({
                            icon: 'success',
                            title: 'Confirmed!',
                            showConfirmButton: false,
                            timer: 1200
                        }).then(() => location.reload());
                        else btn.prop('disabled', false).html('<i class="bi bi-check2-all me-1"></i>Confirm Selected');
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
            order: [
                [2, 'asc']
            ],
            columnDefs: [{
                orderable: false,
                targets: [0, 7]
            }]
        });
    </script>
    <script>
    // tf-bankreg-r1: a flagged payee account is reviewed here by a holder of the verification or batch privilege
    (function () {
        document.querySelectorAll('.tf-acc-review').forEach(function (b) {
            b.addEventListener('click', function () {
                var dec = b.getAttribute('data-decision'), vnum = b.getAttribute('data-vnum');
                var note = window.prompt((dec === 'keep' ? 'Keep the account as approved' : 'Take the registry\'s current details') + ' for Payment Voucher No. ' + vnum + '. Reason (required):');
                if (note === null || !note.trim()) { return; }
                var body = new URLSearchParams({ ref_type: 'voucher', ref_id: vnum, decision: dec, note: note.trim(), '<?= csrf_token() ?>': '<?= csrf_hash() ?>' });
                fetch('<?= base_url('tour/tour_payment_dashboard/account_review') ?>', { method: 'POST', credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, body: body })
                    .then(function (r) { return r.json(); }).then(function (j) { alert(j.message || 'Done'); if (j.status === 200) { location.reload(); } })
                    .catch(function () { alert('The review could not be saved.'); });
            });
        });
    })();
    </script>
</body>

</html>