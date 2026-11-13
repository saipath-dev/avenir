<?php $hideApprovalActions = (int) (session()->get('group_id')) === 25; ?>
<?= view('layouts/head') ?>
<style type="text/css">
    :root {
        --pd-radius: 16px;
        --pd-blue: #2563eb;
        --pd-indigo: #4f46e5;
        --pd-green: #059669;
        --pd-amber: #d97706;
        --pd-surface: #f0f4ff;
        --pd-card-shadow: 0 4px 24px rgba(37, 99, 235, 0.08), 0 1.5px 6px rgba(17,24,39,0.05);
    }

    body { background: #f0f4ff; }

    /* ── Page header ── */
    .pd-page-header {
        background: linear-gradient(135deg, #1e3a8a 0%, #312e81 100%);
        border-radius: var(--pd-radius);
        padding: 1.4rem 1.75rem;
        margin-bottom: 1.75rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
        box-shadow: 0 8px 30px rgba(37,99,235,0.18);
    }

    .pd-page-header .pd-title-block h4 {
        color: #fff;
        font-weight: 800;
        font-size: 1.35rem;
        letter-spacing: -0.02em;
        margin: 0 0 0.15rem 0;
    }

    .pd-page-header .pd-title-block small {
        color: rgba(255,255,255,0.65);
        font-size: 0.8rem;
        font-weight: 400;
    }

    .pd-date-pill {
        background: rgba(255,255,255,0.12);
        border: 1px solid rgba(255,255,255,0.22);
        border-radius: 50px;
        padding: 0.4rem 1rem;
        color: #fff;
        font-size: 0.82rem;
        display: flex;
        align-items: center;
        gap: 0.4rem;
        backdrop-filter: blur(4px);
    }

    /* ── Bank balance chips ── */
    .pd-bank-strip {
        display: flex;
        flex-wrap: wrap;
        gap: 0.6rem;
        margin-bottom: 1.5rem;
    }

    .pd-bank-chip {
        display: flex;
        align-items: center;
        gap: 0.7rem;
        background: #fff;
        border: 1.5px solid #e0e7ff;
        border-radius: 14px;
        padding: 0.55rem 1rem 0.55rem 0.7rem;
        box-shadow: 0 2px 10px rgba(79,70,229,0.07);
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }

    .pd-bank-chip:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(79,70,229,0.13);
    }

    .pd-bank-icon {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #ede9fe, #e0e7ff);
        color: #4338ca;
        font-size: 1rem;
        flex-shrink: 0;
    }

    .pd-bank-name {
        font-size: 0.66rem;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        line-height: 1.1;
    }

    .pd-bank-amt {
        font-size: 0.94rem;
        font-weight: 700;
        line-height: 1.3;
    }

    /* ── Section cards ── */
    .pd-section {
        border: none;
        border-radius: var(--pd-radius);
        overflow: hidden;
        box-shadow: var(--pd-card-shadow);
        margin-bottom: 1.75rem;
    }

    .pd-section-header {
        border: none;
        padding: 1.1rem 1.5rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 0.5rem;
        position: relative;
        overflow: hidden;
    }

    .pd-section-header::after {
        content: '';
        position: absolute;
        right: -30px;
        top: -30px;
        width: 120px;
        height: 120px;
        border-radius: 50%;
        background: rgba(255,255,255,0.08);
        pointer-events: none;
    }

    .pd-section-header h5 {
        margin: 0;
        font-weight: 700;
        font-size: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: #fff;
    }

    .pd-section-header h5 .label-sub {
        color: rgba(255,255,255,0.75);
        font-weight: 400;
        font-size: 0.88rem;
    }

    .pd-section-header .pd-total-badge {
        background: rgba(255,255,255,0.18);
        border: 1px solid rgba(255,255,255,0.3);
        border-radius: 50px;
        padding: 0.35em 0.95em;
        font-size: 0.82rem;
        font-weight: 700;
        color: #fff;
        backdrop-filter: blur(4px);
    }

    .pd-header-requests { background: linear-gradient(120deg, #1d4ed8 0%, #4338ca 100%); }
    .pd-header-expenses  { background: linear-gradient(120deg, #047857 0%, #059669 100%); }
    .pd-header-utility   { background: linear-gradient(120deg, #1a1a1a 0%, #d97706 100%); }
    .pd-header-allocation { background: linear-gradient(120deg, #be185d 0%, #9d174d 100%); }

    .pd-section .card-body {
        background: #fff;
        padding: 1.35rem 1.5rem;
    }

    /* ── Totals summary bar ── */
    .pd-totals-bar {
        display: flex;
        flex-wrap: wrap;
        gap: 0.6rem;
        justify-content: flex-end;
        margin-bottom: 1.1rem;
    }

    .pd-total-pill {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        background: #f5f7ff;
        border: 1.5px solid #e0e7ff;
        border-radius: 50px;
        padding: 0.4rem 1rem;
        font-size: 0.82rem;
        color: #475569;
    }

    .pd-total-pill .pd-pill-label { font-weight: 500; }
    .pd-total-pill .pd-pill-val   { font-weight: 800; font-size: 0.94rem; }

    /* ── Tables ── */
    .table {
        border-collapse: collapse;
        border-spacing: 0;
    }

    .table thead th {
        background: #f1f5ff;
        color: #3730a3;
        font-size: 0.71rem;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        font-weight: 700;
        border-bottom: 2px solid #dde3f5;
        white-space: nowrap;
        padding: 0.7rem 0.9rem;
    }

    .table tbody td {
        vertical-align: middle;
        font-size: 0.875rem;
        border-bottom: 1px solid #f0f4ff;
        padding: 0.65rem 0.9rem;
        color: #374151;
    }

    .table-hover tbody tr:hover {
        background-color: #f5f8ff;
    }

    .table-striped tbody tr:nth-of-type(odd) {
        background-color: #fafbff;
    }

    /* ── Payee group rows ── */
    .payee-group-row {
        background: linear-gradient(90deg, #eef2ff 0%, #f0f4ff 100%);
        cursor: pointer;
        transition: background 0.15s ease;
    }

    .payee-group-row:hover {
        background: linear-gradient(90deg, #e0e7ff 0%, #ede9fe 100%);
    }

    .payee-group-row td {
        font-weight: 700;
        color: #1e293b;
        border-bottom: 1px solid #dde3f5;
    }

    .payee-group-row .chevron-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 22px;
        height: 22px;
        border-radius: 50%;
        background: rgba(99,102,241,0.12);
        color: #4338ca;
        font-size: 0.7rem;
        transition: transform 0.22s ease;
    }

    .payee-group-row.expanded .chevron-icon {
        transform: rotate(90deg);
        background: #4338ca;
        color: #fff;
    }

    .payee-detail-row { display: none; }
    .payee-detail-row.show { display: table-row; }

    .payee-detail-wrap {
        padding: 1rem 1.25rem 1.25rem 3rem;
        background: #f8faff;
        border-top: 2px solid #e0e7ff;
    }

    .payee-detail-inner-table thead th {
        background: #ede9fe;
        color: #4338ca;
        font-size: 0.68rem;
        border-bottom: 2px solid #c4b5fd;
    }

    .payee-count-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #4f46e5, #7c3aed);
        color: #fff;
        border-radius: 50px;
        padding: 0.12rem 0.6rem;
        font-size: 0.7rem;
        font-weight: 800;
        margin-left: 0.5rem;
        min-width: 22px;
        box-shadow: 0 2px 6px rgba(79,70,229,0.25);
    }

    /* ── Footer action bar ── */
    .payee-detail-footer {
        background: linear-gradient(90deg, #f0f4ff, #ede9fe);
        border-radius: 10px;
        padding: 0.65rem 1rem;
        margin-top: 0.75rem;
        display: flex;
        gap: 0.6rem;
        align-items: center;
        flex-wrap: wrap;
        border: 1.5px solid #e0e7ff;
    }

    .payee-selected-count {
        font-size: 0.82rem;
        color: #4338ca;
        font-weight: 600;
        margin-right: 0.5rem;
    }

    /* ── Buttons ── */
    .btn {
        transition: 0.18s ease;
        font-weight: 600;
    }

    .btn-sm {
        border-radius: 8px;
        font-size: 0.78rem;
        padding: 0.35rem 0.85rem;
    }

    .btn-success {
        background: linear-gradient(135deg, #059669, #10b981);
        border: none;
        box-shadow: 0 3px 10px rgba(5,150,105,0.25);
    }

    .btn-success:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(5,150,105,0.3); }

    .btn-danger {
        background: linear-gradient(135deg, #dc2626, #ef4444);
        border: none;
        box-shadow: 0 3px 10px rgba(220,38,38,0.2);
    }

    .btn-danger:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(220,38,38,0.28); }

    /* checkbox style */
    .row-check, .payee-select-all {
        width: 16px;
        height: 16px;
        cursor: pointer;
        accent-color: #4338ca;
    }
</style>

<body class="bg-light">

    <!-- Begin page -->
    <div id="layout-wrapper">

        <?= view('layouts/header') ?>
        <?= view('layouts/sidebar') ?>

        <!-- Start right Content here -->
        <div class="main-content">

            <div class="page-content">
                <div class="container-fluid">

                    <!-- Page Header -->
                    <div class="pd-page-header">
                        <div class="pd-title-block">
                            <h4><i class="bi bi-shield-check me-2 opacity-75"></i>Payment Approval</h4>
                            <small>Review and approve pending payment requests</small>
                        </div>
                        <div class="pd-date-pill">
                            <i class="bi bi-calendar3"></i><?= date('Y-m-d') ?>
                        </div>
                        <div class="pd-date-pill" id="usdRateWidget">
                            <i class="bi bi-currency-exchange"></i>
                            <span>USD Rate:</span>
                            <span id="usdRateDisplay"><?= isset($today_usd_rate) && $today_usd_rate !== null ? number_format((float)$today_usd_rate, 2) : '—' ?></span>
                            <input type="number" step="0.01" id="usdRateInput" class="form-control form-control-sm d-none" style="width:80px;display:inline-block;">
                            <button type="button" class="btn btn-sm btn-link p-0 ms-1 text-white" id="usdRateEditBtn" title="Update today's USD rate"><i class="bi bi-pencil"></i></button>
                        </div>
                    </div>
                    <?php /* tf-paydesk-r2: with Supplier Payables in place an approval raises no voucher — the approved payment waits under its supplier */ try { $tpdAcc = \App\Libraries\TourPayments\Batches::accumulates(); } catch (\Throwable $tpdE) { $tpdAcc = false; } ?>
                    <?php if ($tpdAcc): ?>
                    <div class="alert alert-light border d-flex flex-wrap align-items-center gap-2 py-2 mb-3" style="border-left:4px solid #842484 !important;">
                        <i class="bi bi-collection" style="color:#842484;"></i><span class="small">Approved payments wait under their supplier in <b>Supplier Payables</b>; management then creates the supplier's batch, which issues the voucher for the accountant.</span>
                        <a class="btn btn-sm ms-auto" style="background:#842484;color:#fff;" href="<?= base_url('tour/tour_payment_dashboard/payables') ?>"><i class="bi bi-arrow-right-circle me-1"></i>Open Supplier Payables</a>
                    </div>
                    <?php endif; ?>

                    <!-- Bank Balances -->
                    <?php if (!empty($bank_balances)): ?>
                    <div class="pd-bank-strip">
                        <?php foreach ($bank_balances as $bank): ?>
                        <div class="pd-bank-chip">
                            <div class="pd-bank-icon"><i class="bi bi-bank2"></i></div>
                            <div>
                                <div class="pd-bank-name"><?= esc($bank['PName']) ?></div>
                                <div class="pd-bank-amt <?= (float)$bank['balance'] < 0 ? 'text-danger' : 'text-success' ?>">
                                    <?= number_format((float)$bank['balance'], 2) ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <?= view('pages/tour/partials/pa_upcoming') ?><?php /* tf-pa-upcoming-r1: status cards, Next 15 days review list, one search */ ?>

                    <!-- Payment Requests — grouped by Payee -->
                    <div class="card pd-section">
                        <div class="card-header pd-section-header pd-header-requests">
                            <h5>
                                <i class="bi bi-receipt-cutoff"></i>
                                Payment Requests
                                <span class="label-sub">&nbsp;— Grouped by Payee</span>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex flex-wrap gap-3 align-items-center justify-content-between mb-3">
                                <div class="position-relative" style="max-width:340px; width:100%;">
                                    <i class="bi bi-search" style="position:absolute; left:0.85rem; top:50%; transform:translateY(-50%); color:#9ca3af; font-size:0.85rem;"></i>
                                    <input type="text" id="payeeSearchInput" class="form-control form-control-sm" style="padding-left:2.1rem; border-radius:50px; border:1.5px solid #e0e7ff;" placeholder="Search Tour Manual ID or Payee Name...">
                                </div>
                                <div class="pd-totals-bar mb-0">
                                    <div class="pd-total-pill">
                                        <span class="pd-pill-label">Total Payable ($)</span>
                                        <span class="pd-pill-val text-primary">$ <?= number_format((float)($payment_requests_total_usd ?? 0), 2) ?></span>
                                    </div>
                                    <div class="pd-total-pill">
                                        <span class="pd-pill-label">Total Payable (LKR)</span>
                                        <span class="pd-pill-val text-success">LKR <?= number_format((float)($payment_requests_total_lkr ?? 0), 2) ?></span>
                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-hover align-middle" id="payeeTable">
                                    <thead>
                                        <tr>
                                            <th style="width:36px;"></th>
                                            <th style="width:32px;"></th>
                                            <th>Supplier</th>
                                            <?php if (!empty($tpd_registry)): ?><th>Account holder</th><th>Bank</th><th>Branch</th><th>Account number</th><?php endif; ?><?php /* tf-bankreg-r1 */ ?>
                                            <th class="text-end">Total Payable</th>
                                            <th class="text-end">Requests</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($paymentRequestsByPayee)): ?>
                                            <?php foreach ($paymentRequestsByPayee as $gi => $group): ?>
                                                <?php $groupId = 'payee-group-' . $gi; ?>
                                                <?php $groupManualIds = implode(' ', array_map(function ($r) { return $r['manual_id'] ?? ''; }, $group['requests'])); ?>
                                                <?php /* tf-pa-tabs-r1: the group's tab — a request's type; an activity by its payee's name (PayDesk::activityGroup, the Payment Desk's rule) */
                                                    $gt = $group['requests'][0]['type'] ?? ''; $gcu = $group['requests'][0]['create_user'] ?? '';
                                                    $gTab = $gt === 'Hotel' ? 'hotel' : (($gt === 'Transport Advance' || $gcu === 'Transport Agent') ? 'advance' : (\App\Libraries\TourPayments\PayDesk::activityGroup((string) $group['payee']) === 'meal' ? 'restaurant' : 'entrance')); ?>
                                                <tr class="payee-group-row" data-cat="<?= $gTab ?>" data-target="#<?= $groupId ?>" data-payee-name="<?= esc(strtolower($group['payee'])) ?>" data-manual-ids="<?= esc(strtolower($groupManualIds)) ?>">
                                                    <td>
                                                        <input type="checkbox" class="payee-select-all" data-payee="<?= esc($group['payee']) ?>" title="Select all for this payee" onclick="event.stopPropagation();">
                                                    </td>
                                                    <td>
                                                        <span class="chevron-icon"><i class="bi bi-chevron-right"></i></span>
                                                    </td>
                                                    <td>
                                                        <?= esc($group['payee']) ?>
                                                        <span class="payee-count-badge"><?= count($group['requests']) ?></span>
                                                    </td>
                                                    <?php if (!empty($tpd_registry)): echo view('pages/tour/partials/pa_bank_cells', ['pa' => $tpd_pay_accounts[(string) ($group['requests'][0]['hotel_acc'] ?? '')] ?? null, 'registry_url' => $tpd_registry_url ?? '', 'can_edit' => !empty($tpd_can_edit_registry)]); endif; ?><?php /* tf-bankreg-r1 */ ?>
                                                    <td class="text-end">
                                                        <?php if ($group['total_payable_usd'] > 0): ?>
                                                            <div class="fw-bold text-primary" style="font-size:0.92rem;">$ <?= number_format($group['total_payable_usd'], 2) ?></div>
                                                        <?php endif; ?>
                                                        <?php if ($group['total_payable_lkr'] > 0): ?>
                                                            <div class="fw-semibold text-success" style="font-size:0.82rem;">LKR <?= number_format($group['total_payable_lkr'], 2) ?></div>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-end">
                                                        <span class="badge bg-light text-secondary border" style="font-size:0.8rem;">
                                                            <?= count($group['requests']) ?> item<?= count($group['requests']) !== 1 ? 's' : '' ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                                <tr class="payee-detail-row" id="<?= $groupId ?>">
                                                    <td colspan="<?= !empty($tpd_registry) ? 9 : 5 ?>" class="p-0 border-0">
                                                        <?php $groupType = $group['requests'][0]['type'] ?? ''; ?>
                                                        <div class="payee-detail-wrap">
                                                            <table class="table table-sm table-striped align-middle mb-0 payee-detail-inner-table">
                                                                <thead>
                                                                    <tr>
                                                                        <th style="width:32px;"></th>
                                                                        <th>Tour Name</th>
                                                                        <th>Purpose</th><?php /* tf-pa-tabs-r2 */ ?>
                                                                        <th class="text-end">Pax</th>
                                                                        <th>Start Date</th>
                                                                        <th>Checkin Date</th>
                                                                        <th>Quoted Payment</th>
                                                                        <th>System Hotel Rate</th>
                                                                        <th>Advance / Balance</th>
                                                                        <th>Requested Amount</th>
                                                                        <th>Request Date</th>
                                                                        <th>Status</th><?php /* tf-pa-tabs-r2 */ ?>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <?php foreach ($group['requests'] as $request): ?>
                                                                        <?php $currency = (($request['type'] ?? '') === 'lunch' || ($request['create_user'] ?? '') === 'Transport Agent') ? 'LKR' : '$'; ?>
                                                                        <tr>
                                                                            <td>
                                                                                <input type="checkbox" class="row-check" value="<?= esc($request['id']) ?>" data-type="<?= esc($request['type']) ?>" data-payee="<?= esc($group['payee']) ?>">
                                                                            </td>
                                                                            <td>
                                                                                <strong class="text-dark"><?= esc($request['manual_id']) ?></strong>
                                                                                <br><span class="text-muted" style="font-size:0.78rem;"><?= esc($request['consignee']) ?></span>
                                                                            </td>
                                                                            <td style="font-size:0.82rem;"><?php /* tf-pa-tabs-r2: what the payment is for, in the category's own words */
                                                                                $pur = ['hotel' => 'Hotel stay', 'advance' => 'Driver advance', 'restaurant' => 'Restaurant / meal', 'entrance' => 'Entrance / activity'][$gTab] ?? esc($request['type'] ?? '');
                                                                                $nts = (int) ($request['night_count'] ?? 0); ?>
                                                                                <span class="fw-semibold"><?= $pur ?></span>
                                                                                <?php if ($gTab === 'hotel' && $nts): ?><br><span class="text-muted"><?= $nts ?> night<?= $nts === 1 ? '' : 's' ?></span><?php endif; ?>
                                                                                <br><span class="text-muted"><?= $currency === '$' ? 'USD' : 'LKR' ?></span>
                                                                            </td>
                                                                            <td class="text-end">
                                                                                <span class="badge bg-light text-secondary border" style="font-size:0.78rem;">
                                                                                    <i class="bi bi-people-fill me-1"></i><?= (int) ($request['passenger_count'] ?? 0) ?>
                                                                                </span>
                                                                                <?php if (($request['type'] ?? '') === 'Hotel' && !empty($request['room_types'])): ?>
                                                                                    <br>
                                                                                    <?php foreach ($request['room_types'] as $rt): ?>
                                                                                        <span class="badge bg-light text-secondary border mt-1" style="font-size:0.68rem; white-space:nowrap;"><i class="bi bi-door-closed"></i> <?= esc($rt) ?></span>
                                                                                    <?php endforeach; ?>
                                                                                <?php endif; ?>
                                                                            </td>
                                                                            <td style="font-size:0.82rem; white-space:nowrap;"><?= esc($request['start_date'] ?? '') ?></td>
                                                                            <td style="font-size:0.82rem; white-space:nowrap;">
                                                                                <?php if (!empty($request['all_dates'])):
                                                                                    // Group consecutive dates sharing a month+year into "04, 05, 06 September 2026"
                                                                                    // instead of repeating the month/year on every date.
                                                                                    $dateGroups = [];
                                                                                    foreach ($request['all_dates'] as $d) {
                                                                                        $ts = strtotime($d);
                                                                                        $groupKey = date('F Y', $ts);
                                                                                        $dateGroups[$groupKey][] = date('d', $ts);
                                                                                    }
                                                                                    $dateList = implode(', ', array_map(function ($days, $monthYear) {
                                                                                        return implode(', ', $days) . ' ' . $monthYear;
                                                                                    }, $dateGroups, array_keys($dateGroups)));
                                                                                    $nights = (int) ($request['night_count'] ?? 1);
                                                                                    $daysLeft = (int) floor((strtotime($request['checkin_date']) - strtotime(date('Y-m-d'))) / 86400);
                                                                                ?>
                                                                                    <?= $dateList ?>
                                                                                    <?php if ($nights > 1): ?>
                                                                                        <span class="badge bg-light text-secondary border ms-1" style="font-size:0.68rem;"><?= $nights ?> nights</span>
                                                                                    <?php endif; ?>
                                                                                    <br>
                                                                                    <?php if ($daysLeft < 0): ?>
                                                                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle mt-1"><?= abs($daysLeft) ?>d overdue</span>
                                                                                    <?php elseif ($daysLeft === 0): ?>
                                                                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle mt-1">Today</span>
                                                                                    <?php else: ?>
                                                                                        <span class="badge bg-light text-secondary border mt-1"><?= $daysLeft ?>d</span>
                                                                                    <?php endif; ?>
                                                                                <?php else: ?>
                                                                                    <span class="text-muted">—</span>
                                                                                <?php endif; ?>
                                                                            </td>
                                                                            <td class="text-primary fw-semibold">
                                                                                <?= $currency ?> <?= number_format((float)($request['total_hotel_pay'] ?? 0), 2) ?>
                                                                                <?php if (($request['type'] ?? '') === 'Hotel' && !empty($request['quoted_breakdown'])): ?>
                                                                                    <?php $qbNights = (int) ($request['night_count'] ?? 1); ?>
                                                                                    <table class="mt-1 mb-0" style="font-size:0.7rem; font-weight:500; border-collapse:collapse;">
                                                                                        <?php foreach ($request['quoted_breakdown'] as $qb): ?>
                                                                                            <tr>
                                                                                                <td class="p-0 pe-1 text-secondary" style="border:0; white-space:nowrap;"><?= esc($qb['label']) ?> = <?= $currency ?><?= number_format($qb['rate'], 2) ?></td>
                                                                                            </tr>
                                                                                            <tr>
                                                                                                <td class="p-0 pe-1 pb-1 text-secondary" style="border:0; white-space:nowrap;"><?= $currency ?><?= number_format($qb['rate'], 2) ?> x <?= $qb['count'] ?> for <?= $qbNights ?> Night<?= $qbNights !== 1 ? 's' : '' ?></td>
                                                                                            </tr>
                                                                                        <?php endforeach; ?>
                                                                                    </table>
                                                                                <?php endif; ?>
                                                                            </td>
                                                                            <td style="font-size:0.82rem; white-space:nowrap;">
                                                                                <?php if (($request['type'] ?? '') === 'Hotel' && !empty($request['hotel_cost'])): $hc = $request['hotel_cost']; ?>
                                                                                    <?php /* Hotel cost handoff: payable per the Hotel Rates panel, night by night */ ?>
                                                                                    <div class="fw-semibold" style="color:#065f46;">$ <?= number_format($hc['pay_total'], 2) ?>
                                                                                        <span class="badge <?= $hc['needs_check'] ? 'bg-danger' : (in_array($hc['source'], ['adhoc', 'mixed_adhoc'], true) ? 'bg-success' : 'bg-info text-dark') ?>" style="font-size:0.62rem;"><?= esc($hc['source_label']) ?></span>
                                                                                    </div>
                                                                                    <table class="mt-1 mb-0" style="font-size:0.68rem; border-collapse:collapse;">
                                                                                        <?php foreach ($hc['rows'] as $cr): ?>
                                                                                            <tr>
                                                                                                <td class="p-0 pe-1 text-secondary" style="border:0; white-space:nowrap;"><?= esc(date('d M', strtotime($cr['date']))) ?> <?= esc($cr['room_type'] ?? '') ?> <?= esc($cr['meal_plan'] ?? '') ?></td>
                                                                                                <td class="p-0" style="border:0; white-space:nowrap; color:<?= $cr['source'] === 'adhoc' ? '#065f46' : ($cr['source'] === 'contracted' ? '#1e3a8a' : '#b91c1c') ?>;">
                                                                                                    <?= $cr['source'] === 'adhoc' ? 'ad hoc' : ($cr['source'] === 'contracted' ? 'contract' : 'quoted ⚠') ?> $<?= number_format($cr['pay_total'], 2) ?><?= $cr['supplements'] > 0 ? ' <span class="text-muted">(incl. suppl. ' . number_format($cr['supplements'], 2) . ')</span>' : '' ?>
                                                                                                </td>
                                                                                            </tr>
                                                                                        <?php endforeach; ?>
                                                                                    </table>
                                                                                    <div class="text-muted" style="font-size:0.68rem;">quoted $<?= number_format($hc['quote_total'], 2) ?> · margin $<?= number_format($hc['margin'], 2) ?></div>
                                                                                    <?php if (!empty($hc['offer'])): $of = $hc['offer']; $kindName = ['tactical' => 'Tactical rate', 'early_bird' => 'Early Bird']; ?>
                                                                                        <?php /* tf-offers-r1: published rate · special rate applied · rate used · saving (only a sign-in holding the selection privilege gets $hc['offer']) */ ?>
                                                                                        <div style="font-size:0.68rem;" class="mt-1">
                                                                                            <?php if ($of['pub_total'] !== null): ?><div class="text-muted">Published / contract $<?= number_format((float) $of['pub_total'], 2) ?> · used $<?= number_format((float) $of['pay_total'], 2) ?><?php if (!empty($of['saving'])): ?> · <span style="color:<?= $of['saving'] > 0 ? '#065f46' : '#b91c1c' ?>;">saving $<?= number_format((float) $of['saving'], 2) ?></span><?php endif; ?></div><?php endif; ?>
                                                                                            <?php foreach ($of['offers_used'] as $u): ?><div style="color:#065f46; white-space:normal; max-width:260px;"><?= esc($kindName[$u['kind']] ?? 'Offer') ?> applied: <?= esc($u['name']) ?><?= $u['nights'] < $of['nights'] ? ' (' . (int) $u['nights'] . ' of ' . (int) $of['nights'] . ' nights)' : '' ?></div><?php endforeach; ?>
                                                                                            <?php if ($of['choice_needed']): ?><div class="text-warning fw-semibold" style="white-space:normal; max-width:260px;">Special rate available (<?= count(array_filter($of['offer_options'], static fn ($x) => $x['nights_fit'] > 0)) ?>) — not applied: published rate used until decided on the Tour Payment Dashboard</div><?php endif; ?>
                                                                                            <?php if (!empty($of['choice'])): ?><div class="text-muted"><?= $of['choice']['choice'] === 'published' ? 'published rate kept by ' : 'applied by ' ?><?= esc($of['choice']['chosen_by']) ?> · <?= esc(substr((string) $of['choice']['chosen_at'], 0, 16)) ?></div><?php endif; ?>
                                                                                        </div>
                                                                                    <?php endif; ?>
                                                                                    <?php if ($hc['needs_check']): ?>
                                                                                        <div class="text-danger" style="font-size:0.68rem; white-space:normal; max-width:240px;"><i class="bi bi-exclamation-triangle"></i> <?= esc(implode(' ', $hc['check_notes'])) ?></div>
                                                                                    <?php endif; ?>
                                                                                    <?php if (abs((float) $request['requestAmount'] - $hc['pay_total']) > 0.5): ?>
                                                                                        <div class="text-warning" style="font-size:0.68rem;">requested $<?= number_format((float) $request['requestAmount'], 2) ?> ≠ payable $<?= number_format($hc['pay_total'], 2) ?></div>
                                                                                    <?php endif; ?>
                                                                                <?php elseif (($request['type'] ?? '') === 'Hotel' && !empty($request['system_rates'])): ?>
                                                                                    <table class="mb-0" style="font-size:0.7rem; font-weight:500; border-collapse:collapse;">
                                                                                        <?php foreach ($request['system_rates'] as $sr): ?>
                                                                                            <?php $rtName = !empty($sr['room_type_name']) ? esc($sr['room_type_name']) . ' ' : ''; ?>
                                                                                            <tr>
                                                                                                <?php if ($sr['amount'] !== null): ?>
                                                                                                    <td class="p-0 pe-1" style="border:0; white-space:nowrap; color:#1a1a1a;"><?= $rtName ?><?= esc($sr['label']) ?></td>
                                                                                                    <td class="p-0" style="border:0; white-space:nowrap; color:#1a1a1a;">= $<?= number_format($sr['amount'], 2) ?></td>
                                                                                                <?php else: ?>
                                                                                                    <td class="p-0 pe-1 text-danger" colspan="2" style="border:0; white-space:nowrap;" title="No matching Hotel Rate found">Missing Rate</td>
                                                                                                <?php endif; ?>
                                                                                            </tr>
                                                                                        <?php endforeach; ?>
                                                                                    </table>
                                                                                <?php else: ?>
                                                                                    <span class="text-muted">—</span>
                                                                                <?php endif; ?>
                                                                            </td>
                                                                            <td style="font-size:0.82rem; white-space:nowrap;">
                                                                                <span class="text-success fw-semibold"><?= $currency ?> <?= number_format((float)($request['total_hotel_advance'] ?? 0), 2) ?></span>
                                                                                <br><span class="text-danger"><?= $currency ?> <?= number_format((float)($request['due_amount'] ?? 0), 2) ?></span>
                                                                            </td>
                                                                            <td class="fw-bold" style="color:#7c3aed;">
                                                                                <?= $currency ?> <?= number_format((float)($request['requestAmount'] ?? 0), 2) ?>
                                                                                <?php if (($request['type'] ?? '') === 'Hotel' && !empty($request['lkr_amount'])): ?>
                                                                                    <br><small class="text-success fw-semibold">LKR <?= number_format($request['lkr_amount'], 2) ?> <span class="text-muted fw-normal">(@ <?= number_format($request['dollar_rate'], 2) ?>)</span></small>
                                                                                <?php endif; ?>
                                                                            </td>
                                                                            <td class="text-secondary" style="font-size:0.82rem;">
                                                                                <?= esc($request['payment_request_date']) ?>
                                                                                <br><span class="text-muted" style="font-size:0.72rem;"><?= esc($request['payment_request_user']) ?></span>
                                                                            </td>
                                                                            <td><span class="pau-st aw" style="margin-left:0;">Awaiting approval</span></td><?php /* tf-pa-tabs-r2 */ ?>
                                                                        </tr>
                                                                    <?php endforeach; ?>
                                                                </tbody>
                                                            </table>
                                                            <div class="payee-detail-footer">
                                                                <i class="bi bi-cursor-fill text-primary" style="font-size:0.8rem;"></i>
                                                                <span class="payee-selected-count" data-payee="<?= esc($group['payee']) ?>">0 selected</span>
                                                                <?php if (!$hideApprovalActions): ?>
                                                                <button class="btn btn-success btn-sm payee-accept-btn" data-payee="<?= esc($group['payee']) ?>" disabled>
                                                                    <i class="bi bi-check-lg"></i> Accept Selected
                                                                </button>
                                                                <button class="btn btn-danger btn-sm payee-reject-btn" data-payee="<?= esc($group['payee']) ?>" disabled>
                                                                    <i class="bi bi-x-lg"></i> Reject Selected
                                                                </button>
                                                                <?php endif; ?>
                                                                <!-- <button class="btn btn-sm btn-create-voucher" style="background:linear-gradient(135deg,#7c3aed,#4f46e5);color:#fff;border:none;box-shadow:0 3px 10px rgba(124,58,237,0.25);" data-payee="<?//= esc($group['payee']) ?>" disabled>
                                                                    <i class="bi bi-receipt"></i> Create Voucher
                                                                </button> -->
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center text-muted py-5">
                                                    <i class="bi bi-inbox" style="font-size:2rem; display:block; margin-bottom:0.5rem; opacity:0.4;"></i>
                                                    No payment requests found.
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Driver Settlement / Approved Expenses -->
                    <div class="card pd-section">
                        <div class="card-header pd-section-header pd-header-expenses">
                            <h5>
                                <i class="bi bi-person-check-fill"></i>
                                Driver Settlements
                                <span class="label-sub">&nbsp;— expenses approved, settlement payment due</span>
                            </h5>
                            <?php
                            $grandTotalPayable = array_sum(array_column($approvedExpensesByDriver ?? [], 'total_lkr_payable'));
                            ?>
                            <span class="pd-total-badge">Total Payable: Rs. <?= number_format($grandTotalPayable, 2) ?></span>
                        </div>
                        <div class="card-body">
                            <div class="mb-3 position-relative" style="max-width:340px; width:100%;">
                                <i class="bi bi-search" style="position:absolute; left:0.85rem; top:50%; transform:translateY(-50%); color:#9ca3af; font-size:0.85rem;"></i>
                                <input type="text" id="driverSearchInput" class="form-control form-control-sm" style="padding-left:2.1rem; border-radius:50px; border:1.5px solid #e0e7ff;" placeholder="Search Tour Manual ID or Driver Name...">
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle" id="approvedExpensesTable">
                                    <thead>
                                        <tr>
                                            <th style="width:32px;"></th>
                                            <th>Driver</th>
                                            <?php if (!empty($tpd_registry)): ?><th>Account holder</th><th>Bank</th><th>Branch</th><th>Account number</th><?php endif; ?><?php /* tf-bankreg-r1 */ ?>
                                            <th class="text-end">Total Payable (LKR)</th>
                                            <th class="text-end">Tours</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($approvedExpensesByDriver)): ?>
                                            <?php foreach ($approvedExpensesByDriver as $di => $dgroup): ?>
                                                <?php $dgroupId = 'driver-group-' . $di; ?>
                                                <?php $dgroupManualIds = implode(' ', array_map(function ($t) { return $t['manual_id'] ?? ''; }, $dgroup['tours'])); ?>
                                                        <tr class="payee-group-row" data-cat="settlement" data-target="#<?= $dgroupId ?>" data-payee-name="<?= esc(strtolower($dgroup['driver_name'])) ?>" data-manual-ids="<?= esc(strtolower($dgroupManualIds)) ?>">
                                                    <td>
                                                        <span class="chevron-icon"><i class="bi bi-chevron-right"></i></span>
                                                    </td>
                                                    <td>
                                                        <i class="bi bi-person-circle text-success me-1"></i>
                                                        <strong><?= esc($dgroup['driver_name']) ?></strong>
                                                        <span class="payee-count-badge"><?= count($dgroup['tours']) ?></span>
                                                    </td>
                                                    <?php if (!empty($tpd_registry)): echo view('pages/tour/partials/pa_bank_cells', ['pa' => $tpd_pay_accounts[(string) ($dgroup['tours'][0]['driver'] ?? '')] ?? null, 'registry_url' => $tpd_registry_url ?? '', 'can_edit' => !empty($tpd_can_edit_registry)]); endif; ?><?php /* tf-bankreg-r1 */ ?>
                                                    <td class="text-end fw-bold text-success">Rs. <?= number_format($dgroup['total_lkr_payable'], 2) ?></td>
                                                    <td class="text-end">
                                                        <span class="badge bg-light text-secondary border" style="font-size:0.8rem;">
                                                            <?= count($dgroup['tours']) ?> tour<?= count($dgroup['tours']) !== 1 ? 's' : '' ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                                <tr class="payee-detail-row" id="<?= $dgroupId ?>">
                                                    <td colspan="<?= !empty($tpd_registry) ? 8 : 4 ?>" class="p-0 border-0">
                                                        <div class="payee-detail-wrap">
                                                            <table class="table table-sm table-striped align-middle mb-0 payee-detail-inner-table">
                                                                <thead>
                                                                    <tr>
                                                                        <th style="width:32px;"></th>
                                                                        <th>Manual ID</th>
                                                                        <th>Tour Name</th>
                                                                        <th class="text-end">Pax</th>
                                                                        <th>Start Date</th>
                                                                        <th>End Date</th>
                                                                        <th class="text-end">Total Payable</th>
                                                                        <th>Status</th><?php /* tf-pa-tabs-r2 */ ?>
                                                                        <th>View</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <?php foreach ($dgroup['tours'] as $tour): ?>
                                                                        <tr>
                                                                            <td>
                                                                                <?php if ($tour['tour_cancel'] != 1): ?>
                                                                                    <input type="checkbox" class="driver-tour-check"
                                                                                        value="<?= esc($tour['tour_id']) ?>"
                                                                                        data-driver="<?= esc($dgroup['driver_name']) ?>"
                                                                                        data-manual-id="<?= esc($tour['manual_id']) ?>"
                                                                                        data-payable="<?= (float)($tour['total_lkr_payable'] ?? 0) ?>"
                                                                                        onclick="event.stopPropagation();">
                                                                                <?php endif; ?>
                                                                            </td>
                                                                            <td><strong class="text-danger"><?= esc($tour['manual_id']) ?></strong></td>
                                                                            <td>
                                                                                <strong class="text-dark"><?= esc($tour['consignee']) ?></strong>
                                                                                <br><span class="text-muted" style="font-size:0.78rem;"><?= esc($tour['create_user']) ?></span>
                                                                            </td>
                                                                            <td class="text-end">
                                                                                <span class="badge bg-light text-secondary border" style="font-size:0.78rem;">
                                                                                    <i class="bi bi-people-fill me-1"></i><?= (int) ($tour['passenger_count'] ?? 0) ?>
                                                                                </span>
                                                                            </td>
                                                                            <td style="font-size:0.83rem;"><?= esc($tour['start_date']) ?></td>
                                                                            <td style="font-size:0.83rem;"><?= esc($tour['end_date']) ?></td>
                                                                            <td class="text-end fw-bold text-danger">Rs. <?= number_format((float)($tour['total_lkr_payable'] ?? 0), 2) ?></td>
                                                                            <td><?php /* tf-pa-tabs-r2 */ ?><?php if ($tour['tour_cancel'] == 1): ?><span class="pau-st" style="margin-left:0;background:#fde8e8;color:#9b1c1c;">Tour cancelled</span><?php else: ?><span class="pau-st ok" style="margin-left:0;">Approved expenses · settlement due</span><?php endif; ?></td>
                                                                            <td>
                                                                                <?php if ($tour['tour_cancel'] == 1): ?>
                                                                                    <button class="btn btn-danger btn-sm" disabled>Cancelled</button>
                                                                                <?php else: ?>
                                                                                    <button class="btn btn-success btn-sm" onclick="window.open('<?= base_url('tour/tour_accounts/approved_expenses_report/' . esc($tour['manual_id']) . '/' . esc($tour['driver'] ?? '')) ?>', '_blank')">
                                                                                        <i class="bi bi-eye"></i> View
                                                                                    </button>
                                                                                <?php endif; ?>
                                                                            </td>
                                                                        </tr>
                                                                    <?php endforeach; ?>
                                                                </tbody>
                                                            </table>
                                                            <div class="payee-detail-footer">
                                                                <i class="bi bi-cursor-fill text-success" style="font-size:0.8rem;"></i>
                                                                <span class="driver-selected-count" data-driver="<?= esc($dgroup['driver_name']) ?>">0 selected</span>
                                                                <?php if (!$hideApprovalActions): ?>
                                                                <button class="btn btn-success btn-sm driver-accept-btn" data-driver="<?= esc($dgroup['driver_name']) ?>" disabled>
                                                                    <i class="bi bi-check-lg"></i> Accept Selected
                                                                </button>
                                                                <button class="btn btn-danger btn-sm driver-reject-btn" data-driver="<?= esc($dgroup['driver_name']) ?>" disabled>
                                                                    <i class="bi bi-x-lg"></i> Reject Selected
                                                                </button>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="5" class="text-center text-muted py-5">
                                                    <i class="bi bi-inbox" style="font-size:2rem; display:block; margin-bottom:0.5rem; opacity:0.4;"></i>
                                                    No approved expenses found.
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Utility Bills -->
                    <div class="card pd-section">
                        <div class="card-header pd-section-header pd-header-utility">
                            <h5>
                                <i class="bi bi-lightning-charge-fill"></i>
                                Utility Bill Payments
                                <span class="label-sub">&nbsp;— Pending</span>
                            </h5>
                            <?php
                            $utilityBillTotal = array_sum(array_column($utilityBills ?? [], 'amount'));
                            ?>
                            <span class="pd-total-badge">Total: Rs. <?= number_format($utilityBillTotal, 2) ?></span>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover table-custom align-middle" id="utilityBillTable">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Type</th>
                                            <th>Date</th>
                                            <th>Invoice No</th>
                                            <th>Description</th>
                                            <th>Liability Account</th>
                                            <th>Expenses Account</th>
                                            <th>Amount</th>
                                            <th>Req. User</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($utilityBills)) : ?>
                                            <?php $k = 1; ?>
                                            <?php foreach ($utilityBills as $bill) : ?>
                                                <?php
                                                    $typeLabels = [
                                                        'utility_bill'    => '<span class="badge bg-primary">Utility Bill</span>',
                                                        'special_payment' => '<span class="badge bg-warning text-dark">Special Payment</span>',
                                                        'petty_cash'      => '<span class="badge bg-secondary">Petty Cash</span>',
                                                    ];
                                                    $typeLabel = $typeLabels[$bill['payment_type']] ?? '<span class="badge bg-light text-dark">' . esc($bill['payment_type']) . '</span>';
                                                ?>
                                                <tr>
                                                    <td class="text-muted"><?= $k++; ?></td>
                                                    <td><?= $typeLabel ?></td>
                                                    <td style="font-size:0.83rem;"><?= esc($bill['date']); ?></td>
                                                    <td><?= esc($bill['inv_no']); ?></td>
                                                    <td><?= esc($bill['description']); ?></td>
                                                    <td style="font-size:0.82rem;"><?= esc($bill['acc_name_liability']); ?></td>
                                                    <td style="font-size:0.82rem;"><?= esc($bill['acc_name_expenses']); ?></td>
                                                    <td class="text-danger fw-bold">Rs. <?= number_format($bill['amount'], 2) ?></td>
                                                    <td style="font-size:0.83rem;"><?= esc($bill['cashier']); ?></td>
                                                    <td style="white-space:nowrap;">
                                                        <?php if (!$hideApprovalActions): ?>
                                                        <button class="btn btn-success btn-sm btn-utility-accept" data-id="<?= esc($bill['id']); ?>" data-payment-type="<?= esc($bill['payment_type']); ?>">
                                                            <i class="bi bi-check-lg"></i> Accept
                                                        </button>
                                                        <button class="btn btn-danger btn-sm btn-utility-reject ms-1" data-id="<?= esc($bill['id']); ?>">
                                                            <i class="bi bi-x-lg"></i> Reject
                                                        </button>
                                                        <?php else: ?>
                                                        <span class="text-muted" style="font-size:0.8rem;">—</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else : ?>
                                            <tr>
                                                <td colspan="10" class="text-center text-muted py-5">
                                                    <i class="bi bi-inbox" style="font-size:2rem; display:block; margin-bottom:0.5rem; opacity:0.4;"></i>
                                                    No pending utility bill payments found.
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Driver Allocation Requests -->
                    <div class="card pd-section">
                        <div class="card-header pd-section-header pd-header-allocation">
                            <h5>
                                <i class="bi bi-person-plus-fill"></i>
                                Driver Allocation Requests
                                <span class="label-sub">&nbsp;— Unpaid Settlement Override</span>
                            </h5>
                            <span class="pd-total-badge"><?= count($driverAllocationRequests ?? []) ?> pending</span>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle" id="driverAllocationRequestsTable">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Manual ID</th>
                                            <th>Tour Name</th>
                                            <th class="text-end">Pax</th>
                                            <th>Driver</th>
                                            <th>Requested By</th>
                                            <th>Requested Date</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($driverAllocationRequests)): ?>
                                            <?php foreach ($driverAllocationRequests as $ri => $req): ?>
                                                <tr id="dar-row-<?= (int) $req['id'] ?>">
                                                    <td class="text-muted"><?= $ri + 1 ?></td>
                                                    <td><strong class="text-dark"><?= esc($req['manual_id']) ?></strong></td>
                                                    <td><?= esc($req['consignee'] ?? '') ?></td>
                                                    <td class="text-end">
                                                        <span class="badge bg-light text-secondary border" style="font-size:0.78rem;">
                                                            <i class="bi bi-people-fill me-1"></i><?= (int) ($req['passenger_count'] ?? 0) ?>
                                                        </span>
                                                    </td>
                                                    <td><?= esc($req['driver_name']) ?></td>
                                                    <td style="font-size:0.83rem;"><?= esc($req['requested_user'] ?? '-') ?></td>
                                                    <td style="font-size:0.83rem; white-space:nowrap;"><?= esc($req['requested_date'] ?? '-') ?></td>
                                                    <td>
                                                        <?php if (!$hideApprovalActions): ?>
                                                        <button class="btn btn-success btn-sm btn-approve-allocation-request" data-id="<?= (int) $req['id'] ?>" data-driver="<?= esc($req['driver_name']) ?>" data-manual-id="<?= esc($req['manual_id']) ?>">
                                                            <i class="bi bi-check-lg"></i> Approve
                                                        </button>
                                                        <?php else: ?>
                                                        <span class="text-muted" style="font-size:0.8rem;">—</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="8" class="text-center text-muted py-5">
                                                    <i class="bi bi-inbox" style="font-size:2rem; display:block; margin-bottom:0.5rem; opacity:0.4;"></i>
                                                    No pending driver allocation requests.
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <?= view('layouts/brand') ?>

        </div>
    </div>

    <!-- Driver Settlement Accept Modal -->
    <div class="modal fade" id="AcceptDriverSettlementModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5">Accept Driver Settlement</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="javascript:void(0);" id="accept_driver_settlement_form">
                        <input type="hidden" id="ads_tour_ids">
                        <input type="hidden" id="ads_payable_amount">
                        <input type="hidden" id="ads_driver">
                        <div class="mb-3">
                            <label class="form-label">Bank Account<font color="#FF0000"><strong>*</strong></font></label>
                            <select id="ads_bank_acc" class="form-control" required>
                                <option value="">Select a Bank...</option>
                                <?php foreach ($bank_acc as $bank): ?>
                                    <option value="<?= $bank['L_Code'] ?>"><?= $bank['PName'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Date<font color="#FF0000"><strong>*</strong></font></label>
                            <input type="date" id="ads_date" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-success" style="float:right;">Accept</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Bulk Voucher Modal -->
    <div class="modal fade" id="CreateBulkVoucherModal" tabindex="-1" aria-labelledby="CreateBulkVoucherModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header" style="background:linear-gradient(135deg,#7c3aed,#4f46e5);">
                    <h1 class="modal-title fs-5 text-white" id="CreateBulkVoucherModalLabel"><i class="bi bi-receipt me-2"></i>Create Voucher</h1>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="javascript:void(0);" id="create_bulk_voucher_form">
                        <input type="hidden" id="cbv_ids">
                        <input type="hidden" id="cbv_payee">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Bank Account <font color="#FF0000"><strong>*</strong></font></label>
                            <select id="cbv_bank_acc" class="form-control" required>
                                <option value="">Select a Bank...</option>
                                <?php foreach ($bank_acc as $bank): ?>
                                    <option value="<?= $bank['L_Code'] ?>"><?= $bank['PName'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Date</label>
                            <input type="date" id="cbv_date" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div id="cbv_summary" class="mb-3 p-2 rounded" style="background:#f5f3ff;border:1px solid #ede9fe;font-size:0.85rem;color:#4338ca;"></div>
                        <div class="mt-3 d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-sm text-white" style="background:linear-gradient(135deg,#7c3aed,#4f46e5);">
                                <i class="bi bi-receipt me-1"></i>Create Voucher
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="AcceptHotelBankModal" tabindex="-1" aria-labelledby="AcceptHotelBankModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="AcceptHotelBankModalLabel">Accept Payment Requests</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="javascript:void(0);" id="bulk_accept_bank_form">
                        <input type="hidden" id="bab_ids">
                        <input type="hidden" id="bab_status">
                        <div class="mb-3">
                            <label class="form-label">Bank Account<font color="#FF0000"><strong>*</strong></font></label>
                            <select id="bab_bank_acc" class="form-control" style="width: 100%;" required>
                                <option value="">Select a Bank...</option>
                                <?php
                                foreach ($bank_acc as $bank) {
                                    echo "<option value='" . $bank['L_Code'] . "'>" . $bank['PName'] . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Date<font color="#FF0000"><strong>*</strong></font></label>
                            <input type="date" id="bab_date" class="form-control" placeholder="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-success" style="float: right;">
                                Accept
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="AcceptUtilityBillBankModal" tabindex="-1" aria-labelledby="AcceptUtilityBillBankModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="AcceptUtilityBillBankModalLabel">Accept Payment</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="javascript:void(0);" id="accept_utility_bill_bank_form">
                        <input type="hidden" id="aub_id">
                        <div class="mb-3">
                            <label class="form-label">Bank Account<font color="#FF0000"><strong>*</strong></font></label>
                            <select id="aub_bank_acc" class="form-control" style="width: 100%;" required>
                                <option value="">Select a Bank...</option>
                                <?php
                                foreach ($bank_acc as $bank) {
                                    echo "<option value='" . $bank['L_Code'] . "'>" . $bank['PName'] . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Date<font color="#FF0000"><strong>*</strong></font></label>
                            <input type="date" id="aub_date" class="form-control" placeholder="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-success" style="float: right;">
                                Accept
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?= view('layouts/inpage_footer') ?>
    <?= view('layouts/scripts') ?>

    <script src="<?= base_url(); ?>assets/bundles/datatablescripts.bundle.js"></script>
    <script>
        // ===== Daily USD rate quick-entry widget =====
        $('#usdRateEditBtn').on('click', function() {
            const current = $('#usdRateDisplay').text().trim();
            $('#usdRateInput').val(current === '—' ? '' : current).removeClass('d-none').trigger('focus');
            $('#usdRateDisplay').addClass('d-none');
        });

        function saveUsdRate() {
            const val = $('#usdRateInput').val();
            if (val === '' || isNaN(val)) {
                $('#usdRateInput').addClass('d-none');
                $('#usdRateDisplay').removeClass('d-none');
                return;
            }
            $.post("<?php echo base_url() ?>master_files/daily_usd_rate/quick_save", {
                rate_date: '<?= date('Y-m-d') ?>',
                rate: val
            }, function(data) {
                if (data.status === 200) {
                    $('#usdRateDisplay').text(parseFloat(data.rate).toFixed(2));
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: data.message });
                }
                $('#usdRateInput').addClass('d-none');
                $('#usdRateDisplay').removeClass('d-none');
            }, 'json').fail(function() {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Could not save the USD rate.' });
                $('#usdRateInput').addClass('d-none');
                $('#usdRateDisplay').removeClass('d-none');
            });
        }

        $('#usdRateInput').on('blur', saveUsdRate);
        $('#usdRateInput').on('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                $(this).trigger('blur');
            }
        });

        // ===== Payee group expand/collapse =====
        $(document).on('click', '.payee-group-row', function() {
            const target = $(this).data('target');
            $(target).toggleClass('show');
            $(this).toggleClass('expanded');
        });

        // ===== Search filter: Tour Manual ID / Payee or Driver Name =====
        function setupGroupSearch(inputSelector, tableSelector) {
            $(inputSelector).on('input', function() {
                const term = $(this).val().trim().toLowerCase();
                const $rows = $(`${tableSelector} > tbody > tr.payee-group-row`);

                if (term === '') {
                    $rows.show();
                    $(`${tableSelector} > tbody > tr.payee-detail-row`).removeClass('show');
                    $rows.removeClass('expanded');
                    return;
                }

                $rows.each(function() {
                    const $groupRow = $(this);
                    const $detailRow = $($groupRow.data('target'));
                    const name = $groupRow.data('payee-name') || '';
                    const manualIds = String($groupRow.data('manual-ids') || '');
                    const matches = name.indexOf(term) !== -1 || manualIds.indexOf(term) !== -1;

                    $groupRow.toggle(matches);
                    if (matches) {
                        $detailRow.addClass('show');
                        $groupRow.addClass('expanded');
                    } else {
                        $detailRow.removeClass('show');
                        $groupRow.removeClass('expanded');
                    }
                });
            });
        }

        setupGroupSearch('#payeeSearchInput', '#payeeTable');
        setupGroupSearch('#driverSearchInput', '#approvedExpensesTable');

        // ===== Bulk select logic (payment requests), scoped per payee =====
        function getSelectedIdsForPayee(payee) {
            return $(`.row-check[data-payee="${payee}"]:checked`).map(function() { return $(this).val(); }).get();
        }

        // Payee-level select all — checks all requests within that payee's group
        $(document).on('change', '.payee-select-all', function() {
            const payee = $(this).data('payee');
            $(`.row-check[data-payee="${payee}"]`).prop('checked', this.checked);
            updatePayeeFooter(payee);
        });

        // Row-level checkbox — reconciles the payee's own select-all + footer state
        $(document).on('change', '.row-check', function(e) {
            e.stopPropagation();
            const payee = $(this).data('payee');
            const allInPayee = $(`.row-check[data-payee="${payee}"]`).length;
            const checkedInPayee = $(`.row-check[data-payee="${payee}"]:checked`).length;
            $(`.payee-select-all[data-payee="${payee}"]`).prop('checked', allInPayee === checkedInPayee);

            updatePayeeFooter(payee);
        });

        // Prevent row checkbox clicks from toggling the collapse
        $(document).on('click', '.row-check', function(e) {
            e.stopPropagation();
        });

        // Locks a payee's detail row (hides checkboxes, disables action buttons) once
        // accept/reject has been submitted, until the page reloads.
        function lockPayeeGroup(payee) {
            const $scope = $(`.payee-select-all[data-payee="${payee}"]`).closest('tr').next('.payee-detail-row');
            $scope.find('.row-check, .payee-select-all').prop('disabled', true).hide();
            $scope.find('.payee-accept-btn, .payee-reject-btn, .btn-create-voucher').prop('disabled', true);
        }

        function unlockPayeeGroup(payee) {
            const $scope = $(`.payee-select-all[data-payee="${payee}"]`).closest('tr').next('.payee-detail-row');
            $scope.find('.row-check, .payee-select-all').prop('disabled', false).show();
            updatePayeeFooter(payee);
        }

        function submitBulk(ids, status, bank, bankName, payDate, payee) {
            $.post("<?php echo base_url() ?>tour/tour_accounts/bulk_update_payment_request_status", { '<?= csrf_token() ?>': '<?= csrf_hash() ?>',   // tf-paydesk-r7f: the page's security token
                ids: ids,
                status: status,
                bank_acc: bank || '',
                bank_name: bankName || '',
                payment_date: payDate || '<?= date('Y-m-d') ?>'
            }, function(data) {
                if (data.status === 200) {
                    if (payee) lockPayeeGroup(payee);
                    Swal.fire({ icon: 'success', title: 'Done!', text: data.message, showConfirmButton: false, timer: 1500 })
                        .then(() => location.reload());
                } else {
                    Swal.fire({ icon: 'error', title: 'Error!', text: data.message });
                    if (payee) unlockPayeeGroup(payee);
                }
            }, 'json').fail(function (x) {   // tf-paydesk-r7f: a refused request shows the server's reason
                Swal.fire({ icon: 'error', title: 'Not done', text: (x && x.responseJSON && x.responseJSON.message) || 'Something went wrong. Please try again.' });
                if (payee) unlockPayeeGroup(payee);
            });
        }

        function doPayeeAction(payee, status) {
            const ids = getSelectedIdsForPayee(payee);
            if (ids.length === 0) return;

            if (status === '5') {
                Swal.fire({
                    title: `Reject ${ids.length} request(s)?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, Reject!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        submitBulk(ids, status, null, null, null, payee);
                    } else {
                        unlockPayeeGroup(payee);
                    }
                });
                return;
            }

            // Accept — always go through create_bulk_voucher (handles status update + voucher)
            const labels = $(`.row-check[data-payee="${payee}"]:checked`).map(function() {
                return $(this).closest('tr').find('td:nth-child(2) strong').text();
            }).get();

            $("#cbv_ids").val(ids.join(','));
            $("#cbv_payee").val(payee);
            $("#cbv_bank_acc").val('');
            $("#cbv_summary").html(
                '<strong>' + ids.length + ' invoice(s) selected:</strong><br>' +
                labels.map(l => '&nbsp;• ' + l).join('<br>')
            );
            $("#CreateBulkVoucherModal .modal-title").html('<i class="bi bi-check-circle me-2"></i>Accept Payment Requests');
            $("#CreateBulkVoucherModal").modal('show');
        }

        $(document).on('click', '.payee-accept-btn', function() {
            lockPayeeGroup($(this).data('payee'));
            doPayeeAction($(this).data('payee'), '4');
        });
        $(document).on('click', '.payee-reject-btn', function() {
            lockPayeeGroup($(this).data('payee'));
            doPayeeAction($(this).data('payee'), '5');
        });

        // Bulk accept bank modal submit
        $("#bulk_accept_bank_form").on('submit', function(e) {
            e.preventDefault();

            const ids = $("#bab_ids").val().split(',').filter(Boolean);
            const status = $("#bab_status").val();
            const bank = $("#bab_bank_acc").val();
            const bankName = $("#bab_bank_acc option:selected").text();
            const payDate = $("#bab_date").val() || new Date().toISOString().split('T')[0];

            if (!bank) {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Please select a bank account' });
                return;
            }

            $("#AcceptHotelBankModal").modal('hide');
            submitBulk(ids, status, bank, bankName, payDate);
        });

        // ===== Utility bills DataTable =====

        try {
            $('#utilityBillTable').DataTable({
                order: [[1, 'asc']],
                pageLength: 25
            });
        } catch(e) { console.warn('utilityBillTable DataTable:', e); }

        // ===== Utility bill accept/reject (same as payment_requested_dashboard_new) =====
        function submitUtilityAccept(id, bank, bankName, payDate) {
            Swal.fire({
                title: 'Accept this payment?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#78b848',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Accept'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('<?= base_url('accounts/utility_bill/update_status') ?>', {
                        id: id,
                        status: 1,
                        bank: bank || '',
                        bank_name: bankName || '',
                        payment_date: payDate || '<?= date('Y-m-d') ?>'
                    }, function(data) {
                        if (data.status === 200) {
                            Swal.fire({ icon: 'success', title: 'Accepted!', timer: 1500, showConfirmButton: false })
                                .then(() => location.reload());
                        } else {
                            Swal.fire({ icon: 'error', title: 'Error', text: data.message, timer: 2000, showConfirmButton: false });
                        }
                    }, 'json').fail(function(xhr) {
                        Swal.fire({ icon: 'error', title: 'Error', text: 'Permission denied or server error. (' + xhr.status + ')' });
                    });
                }
            });
        }

        $(document).on('click', '.btn-utility-accept', function() {
            const id = $(this).data('id');
            const paymentType = $(this).data('payment-type');

            if (paymentType === 'petty_cash') {
                submitUtilityAccept(id);
                return;
            }

            $("#aub_id").val(id);
            $("#aub_bank_acc").val('');
            $("#AcceptUtilityBillBankModal").modal('show');
        });

        $("#accept_utility_bill_bank_form").on('submit', function(e) {
            e.preventDefault();

            const id = $("#aub_id").val();
            const bank = $("#aub_bank_acc").val();
            const bankName = $("#aub_bank_acc option:selected").text();
            const payDate = $("#aub_date").val() || new Date().toISOString().split('T')[0];

            if (!bank) {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Please select a bank account' });
                return;
            }

            $("#AcceptUtilityBillBankModal").modal('hide');
            submitUtilityAccept(id, bank, bankName, payDate);
        });

        // ===== Driver Settlement accept/reject =====
        function getSelectedToursForDriver(driver) {
            return $(`.driver-tour-check[data-driver="${CSS.escape(driver)}"]:checked`);
        }

        function updateDriverFooter(driver) {
            const $checked = getSelectedToursForDriver(driver);
            $(`.driver-selected-count[data-driver="${CSS.escape(driver)}"]`).text($checked.length + ' selected');
            $(`.driver-accept-btn[data-driver="${CSS.escape(driver)}"], .driver-reject-btn[data-driver="${CSS.escape(driver)}"]`).prop('disabled', $checked.length === 0);
        }

        // Locks a driver's detail row (hides checkboxes, disables action buttons) once
        // accept/reject has been submitted, until the page reloads.
        function lockDriverGroup(driver) {
            const $scope = $(`.driver-selected-count[data-driver="${CSS.escape(driver)}"]`).closest('.payee-detail-wrap');
            $scope.find('.driver-tour-check').prop('disabled', true).hide();
            $scope.find('.driver-accept-btn, .driver-reject-btn').prop('disabled', true);
        }

        function unlockDriverGroup(driver) {
            const $scope = $(`.driver-selected-count[data-driver="${CSS.escape(driver)}"]`).closest('.payee-detail-wrap');
            $scope.find('.driver-tour-check').prop('disabled', false).show();
            updateDriverFooter(driver);
        }

        $(document).on('change', '.driver-tour-check', function(e) {
            e.stopPropagation();
            updateDriverFooter($(this).data('driver'));
        });

        $(document).on('click', '.driver-tour-check', function(e) {
            e.stopPropagation();
        });

        $(document).on('click', '.driver-accept-btn', function() {
            const driver = $(this).data('driver');
            const $checked = getSelectedToursForDriver(driver);
            if ($checked.length === 0) return;

            const tourIds  = $checked.map(function() { return $(this).val(); }).get();
            const totalAmt = $checked.toArray().reduce((s, el) => s + parseFloat($(el).data('payable') || 0), 0);

            lockDriverGroup(driver);

            $("#ads_tour_ids").val(tourIds.join(','));
            $("#ads_payable_amount").val(totalAmt.toFixed(2));
            $("#ads_bank_acc").val('');
            $("#ads_driver").val(driver);
            $("#AcceptDriverSettlementModal").modal('show');
        });

        // If the driver settlement accept modal is dismissed/cancelled without submitting, unlock
        let adsSubmitting = false;
        $("#AcceptDriverSettlementModal").on('hidden.bs.modal', function() {
            if (!adsSubmitting) {
                const driver = $("#ads_driver").val();
                if (driver) unlockDriverGroup(driver);
            }
        });

        $("#accept_driver_settlement_form").on('submit', function(e) {
            e.preventDefault();
            const tourIds  = $("#ads_tour_ids").val().split(',').filter(Boolean);
            const bank     = $("#ads_bank_acc").val();
            const bankName = $("#ads_bank_acc option:selected").text();
            const payDate  = $("#ads_date").val() || new Date().toISOString().split('T')[0];
            const payable  = $("#ads_payable_amount").val();
            const driver   = $("#ads_driver").val();

            if (!bank) {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Please select a bank account' });
                return;
            }

            adsSubmitting = true;
            $("#AcceptDriverSettlementModal").modal('hide');

            // Accept each tour sequentially
            let chain = Promise.resolve();
            let errors = [];
            tourIds.forEach(function(tour_id) {
                chain = chain.then(() =>
                    $.post('<?= base_url('tour/tour_accounts/accept_approved_expenses') ?>', { '<?= csrf_token() ?>': '<?= csrf_hash() ?>',   // tf-paydesk-r7f: the page's security token
                        tour_id: tour_id,
                        bank_acc: bank,
                        bank_name: bankName,
                        payable_amount: payable,
                        payment_date: payDate
                    }, null, 'json').then(function(data) {
                        if (data.status !== 200) errors.push(data.message);
                    }).fail(function(xhr) {
                        errors.push((xhr && xhr.responseJSON && xhr.responseJSON.message) || ('Server error (' + xhr.status + ') for tour ' + tour_id));   // tf-paydesk-r7f
                    })
                );
            });

            chain.then(function() {
                adsSubmitting = false;
                if (errors.length === 0) {
                    Swal.fire({ icon: 'success', title: 'Accepted!', text: tourIds.length + ' tour(s) settled successfully.', timer: 1800, showConfirmButton: false })
                        .then(() => location.reload());
                } else {
                    Swal.fire({ icon: 'error', title: 'Some errors occurred', html: errors.join('<br>') });
                    if (driver) unlockDriverGroup(driver);
                }
            });
        });

        $(document).on('click', '.driver-reject-btn', function() {
            const driver   = $(this).data('driver');
            const $checked = getSelectedToursForDriver(driver);
            if ($checked.length === 0) return;

            const tourIds = $checked.map(function() { return $(this).val(); }).get();

            Swal.fire({
                title: `Reject ${tourIds.length} tour settlement(s)?`,
                input: 'textarea',
                inputPlaceholder: 'Enter rejection reason...',
                inputAttributes: { 'aria-label': 'Rejection reason' },
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Reject!',
                preConfirm: (reason) => {
                    if (!reason || reason.trim() === '') {
                        Swal.showValidationMessage('Rejection reason is required');
                    }
                    return reason;
                }
            }).then((result) => {
                if (!result.isConfirmed) return;
                const reason = result.value;

                let chain  = Promise.resolve();
                let errors = [];
                tourIds.forEach(function(tour_id) {
                    chain = chain.then(() =>
                        $.post('<?= base_url('tour/tour_accounts/reject_approved_expenses') ?>', { '<?= csrf_token() ?>': '<?= csrf_hash() ?>',   // tf-paydesk-r7f: the page's security token
                            tour_id: tour_id,
                            reason: reason
                        }, null, 'json').then(function(data) {
                            if (data.status !== 200) errors.push(data.message);
                        }).fail(function(xhr) {
                            errors.push((xhr && xhr.responseJSON && xhr.responseJSON.message) || ('Server error (' + xhr.status + ') for tour ' + tour_id));   // tf-paydesk-r7f
                        })
                    );
                });

                chain.then(function() {
                    if (errors.length === 0) {
                        lockDriverGroup(driver);
                        Swal.fire({ icon: 'success', title: 'Rejected!', text: tourIds.length + ' tour(s) rejected.', timer: 1800, showConfirmButton: false })
                            .then(() => location.reload());
                    } else {
                        Swal.fire({ icon: 'error', title: 'Some errors occurred', html: errors.join('<br>') });
                    }
                });
            });
        });

        // ===== Create Bulk Voucher =====
        $(document).on('click', '.btn-create-voucher', function() {
            const payee = $(this).data('payee');
            const ids   = getSelectedIdsForPayee(payee);
            if (ids.length === 0) return;

            const labels = $(`.row-check[data-payee="${payee}"]:checked`).map(function() {
                return $(this).closest('tr').find('td:nth-child(2) strong').text();
            }).get();

            $("#cbv_ids").val(ids.join(','));
            $("#cbv_payee").val(payee);
            $("#cbv_bank_acc").val('');
            $("#cbv_summary").html(
                '<strong>' + ids.length + ' invoice(s) selected:</strong><br>' +
                labels.map(l => '&nbsp;• ' + l).join('<br>')
            );
            $("#CreateBulkVoucherModal").modal('show');
        });

        let cbvSubmitting = false;
        $("#create_bulk_voucher_form").on('submit', function(e) {
            e.preventDefault();
            if (cbvSubmitting) return;

            const ids      = $("#cbv_ids").val().split(',').filter(Boolean);
            const bank     = $("#cbv_bank_acc").val();
            const bankName = $("#cbv_bank_acc option:selected").text();

            if (!bank) {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Please select a bank account' });
                return;
            }

            cbvSubmitting = true;
            $("#CreateBulkVoucherModal").modal('hide');

            const payee = $("#cbv_payee").val();

            $.post("<?= base_url('tour/tour_accounts/create_bulk_voucher') ?>", { '<?= csrf_token() ?>': '<?= csrf_hash() ?>',   // tf-paydesk-r7f: the page's security token
                ids: ids,
                bank_acc: bank,
                bank_name: bankName
            }, function(data) {
                cbvSubmitting = false;
                if (data.status === 200) {
                    if (payee) lockPayeeGroup(payee);
                    Swal.fire({ icon: 'success', title: 'Voucher Created!', text: data.message, showConfirmButton: false, timer: 2000 })
                        .then(() => location.reload());
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: data.message });
                    if (payee) unlockPayeeGroup(payee);
                }
            }, 'json').fail(function(xhr) {
                cbvSubmitting = false;
                Swal.fire({ icon: 'error', title: 'Error', text: (xhr && xhr.responseJSON && xhr.responseJSON.message) || ('Server error (' + xhr.status + ')') });   // tf-paydesk-r7f
                if (payee) unlockPayeeGroup(payee);
            });
        });

        // If the accept modal is dismissed/cancelled without submitting, unlock the group
        $("#CreateBulkVoucherModal").on('hidden.bs.modal', function() {
            if (!cbvSubmitting) {
                const payee = $("#cbv_payee").val();
                if (payee) unlockPayeeGroup(payee);
            }
        });

        // Enable/disable Create Voucher button together with Accept/Reject buttons
        function updatePayeeFooter(payee) {
            const ids = getSelectedIdsForPayee(payee);
            $(`.payee-selected-count[data-payee="${payee}"]`).text(ids.length + ' selected');
            const disabled = ids.length === 0;
            $(`.payee-accept-btn[data-payee="${payee}"], .payee-reject-btn[data-payee="${payee}"], .btn-create-voucher[data-payee="${payee}"]`).prop('disabled', disabled);
        }

        // ===== Driver Allocation Request approve =====
        $(document).on('click', '.btn-approve-allocation-request', function() {
            const $btn = $(this);
            const id = $btn.data('id');
            const driver = $btn.data('driver');
            const manualId = $btn.data('manual-id');

            Swal.fire({
                title: 'Approve Allocation?',
                html: `Allocate <strong>${driver}</strong> to tour <strong>${manualId}</strong> despite unpaid settlements?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#be185d',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Approve'
            }).then((result) => {
                if (!result.isConfirmed) return;

                $btn.prop('disabled', true);

                $.post('<?= base_url('dashboard2/transport/driver_allow/approve_driver_allocation_request') ?>', {
                    id: id
                }, function(data) {
                    if (data.status === 200) {
                        Swal.fire({ icon: 'success', title: 'Approved!', text: data.message, timer: 1500, showConfirmButton: false })
                            .then(() => {
                                $(`#dar-row-${id}`).fadeOut(200, function() { $(this).remove(); });
                            });
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: data.message });
                        $btn.prop('disabled', false);
                    }
                }, 'json').fail(function(xhr) {
                    Swal.fire({ icon: 'error', title: 'Error', text: 'Server error (' + xhr.status + ')' });
                    $btn.prop('disabled', false);
                });
            });
        });

        $(document).on('click', '.btn-utility-reject', function() {
            const id = $(this).data('id');
            Swal.fire({
                title: 'Reject this payment?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Reject'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('<?= base_url('accounts/utility_bill/update_status') ?>', {
                        id: id,
                        status: 2
                    }, function(data) {
                        if (data.status === 200) {
                            Swal.fire({ icon: 'success', title: 'Rejected!', timer: 1500, showConfirmButton: false })
                                .then(() => location.reload());
                        } else {
                            Swal.fire({ icon: 'error', title: 'Error', text: data.message, timer: 2000, showConfirmButton: false });
                        }
                    }, 'json');
                }
            });
        });
    </script>

</body>

</html>
