<?= view('layouts/head') ?>

<style>
    body {
        background: #f2f4f7;
    }

    .dp-topbar {
        background: linear-gradient(135deg,#667eea,#764ba2);
        color: #fff;
        padding: 0.85rem 1rem;
        position: sticky;
        top: 0;
        z-index: 1030;
    }

    .dp-topbar-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
    }

    .dp-topbar-logo {
        flex: 0 0 auto;
    }

    .dp-topbar-logo img {
        height: 30px;
        width: auto;
    }

    .dp-topbar-greeting {
        flex: 1 1 auto;
        text-align: center;
        font-weight: 700;
        font-size: 0.95rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        padding: 0 0.4rem;
    }

    .dp-topbar-title {
        flex: 0 0 auto;
        text-align: right;
        font-weight: 700;
        font-size: 0.85rem;
        line-height: 1.2;
    }

    .dp-topbar-title small {
        display: block;
        font-weight: 400;
        opacity: 0.85;
        font-size: 0.7rem;
    }

    .dp-logout-btn {
        flex: 0 0 auto;
        background: rgba(255,255,255,0.15);
        border: none;
        color: #fff;
        width: 34px;
        height: 34px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.05rem;
        margin-left: 0.3rem;
    }

    .dp-logout-btn:active {
        background: rgba(255,255,255,0.3);
    }

    @media (max-width: 400px) {
        .dp-topbar-greeting {
            font-size: 0.8rem;
        }

        .dp-topbar-title {
            font-size: 0.75rem;
        }
    }

    .dp-container {
        max-width: 560px;
        margin: 0 auto;
        padding: 0.75rem 0.6rem 2rem;
    }

    .dp-search {
        margin-bottom: 0.75rem;
    }

    .dp-tour-card {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 1px 4px rgba(0,0,0,0.08);
        margin-bottom: 0.85rem;
        overflow: hidden;
    }

    .dp-tour-head {
        padding: 0.7rem 0.9rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        cursor: pointer;
        gap: 0.5rem;
    }

    .dp-tour-head .dp-tour-id {
        font-weight: 700;
        color: #4b3f8f;
    }

    .dp-tour-head .dp-tour-consignee {
        font-size: 0.78rem;
        color: #6c757d;
    }

    .dp-tour-head .dp-tour-dates {
        font-size: 0.72rem;
        color: #6c757d;
        white-space: nowrap;
    }

    .dp-tour-detail {
        padding: 0 0.9rem 0.6rem;
        font-size: 0.78rem;
        color: #495057;
        display: none;
        border-top: 1px dashed #e9ecef;
        padding-top: 0.5rem;
    }

    .dp-tour-detail.show {
        display: block;
    }

    .dp-tour-body.collapsed {
        display: none;
    }

    .dp-tour-detail-row {
        display: flex;
        justify-content: space-between;
        padding: 0.15rem 0;
    }

    .dp-tour-body {
        padding: 0 0.6rem 0.6rem;
    }

    .dp-finalize-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.4rem 0.3rem 0.5rem;
        gap: 0.5rem;
    }

    .dp-finalize-hint {
        display: flex;
        align-items: flex-start;
        gap: 0.4rem;
        font-size: 0.72rem;
        color: #b45309;
        background: #fff7ed;
        border: 1px solid #fde3c0;
        border-radius: 8px;
        padding: 0.4rem 0.6rem;
        margin: 0 0.3rem 0.4rem;
    }

    .dp-day-card {
        background: #fafbfe;
        border: 1px solid #eceffb;
        border-radius: 10px;
        padding: 0.6rem 0.7rem;
        margin-bottom: 0.55rem;
        scroll-margin-top: 90px;
    }

    .dp-day-card.dp-day-today {
        background: #f2f0ff;
        border: 1.5px solid #8b7ff0;
    }

    .dp-day-card.dp-day-flash {
        animation: dp-flash 1.2s ease-out;
    }

    @keyframes dp-flash {
        0%, 100% { box-shadow: none; }
        30% { box-shadow: 0 0 0 4px rgba(118, 75, 162, 0.35); }
    }

    .dp-day-date {
        font-weight: 700;
        font-size: 0.85rem;
        margin-bottom: 0.4rem;
        display: flex;
        align-items: center;
        gap: 0.4rem;
    }

    .dp-today-badge {
        background: #764ba2;
        color: #fff;
        font-size: 0.65rem;
        font-weight: 700;
        padding: 0.1rem 0.45rem;
        border-radius: 20px;
        text-transform: uppercase;
        letter-spacing: 0.02em;
    }

    .dp-today-jump-btn {
        position: fixed;
        bottom: 1.1rem;
        right: 1.1rem;
        z-index: 1040;
        border-radius: 30px;
        padding: 0.6rem 1rem;
        font-size: 0.8rem;
        font-weight: 700;
        box-shadow: 0 2px 10px rgba(0,0,0,0.25);
        background: linear-gradient(135deg,#667eea,#764ba2);
        color: #fff;
        border: none;
    }

    .dp-toast {
        position: fixed;
        left: 50%;
        bottom: 1.4rem;
        transform: translate(-50%, 20px);
        background: #1f9d55;
        color: #fff;
        padding: 0.65rem 1.1rem;
        border-radius: 30px;
        font-size: 0.85rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.4rem;
        box-shadow: 0 4px 14px rgba(0,0,0,0.25);
        z-index: 1090;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.25s ease, transform 0.25s ease;
        max-width: calc(100vw - 2rem);
    }

    .dp-toast.dp-toast-show {
        opacity: 1;
        transform: translate(-50%, 0);
    }

    .dp-km-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.25rem 0;
        border-bottom: 1px solid #f0f1f6;
        font-size: 0.82rem;
    }

    .dp-km-row:last-of-type {
        border-bottom: none;
    }

    .dp-km-label {
        color: #6c757d;
        display: flex;
        align-items: center;
        gap: 0.4rem;
    }

    .dp-km-value {
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.4rem;
    }

    .dp-thumb {
        width: 28px;
        height: 28px;
        object-fit: cover;
        border-radius: 5px;
        border: 1px solid #dee2e6;
        cursor: pointer;
    }

    .dp-distance-badge {
        font-size: 0.78rem;
    }

    .dp-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.4rem;
        margin-top: 0.5rem;
    }

    .dp-actions .btn {
        font-size: 0.82rem;
        padding: 0.6rem 0.6rem;
        min-height: 44px;
        flex: 1 1 auto;
    }

    .dp-photo-picker {
        display: flex;
        gap: 0.5rem;
    }

    .dp-photo-picker-btn {
        flex: 1 1 auto;
        min-height: 44px;
        font-size: 0.85rem;
    }

    .dp-km-input {
        font-size: 1.6rem;
        font-weight: 700;
        text-align: center;
        padding: 0.65rem 0.5rem;
        height: auto;
    }

    .dp-expense-toggle {
        width: 100%;
        text-align: left;
        background: #fff;
        border: 1px solid #eee;
        border-radius: 8px;
        padding: 0.35rem 0.6rem;
        font-size: 0.76rem;
        color: #495057;
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 0.5rem;
    }

    .dp-expense-list {
        display: none;
        margin-top: 0.4rem;
    }

    .dp-expense-list.show {
        display: block;
    }

    .dp-expense-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.35rem 0.1rem;
        border-bottom: 1px solid #f4f4f8;
        font-size: 0.75rem;
    }

    .dp-expense-item:last-child {
        border-bottom: none;
    }

    .dp-expense-item .dp-expense-info {
        flex: 1;
        min-width: 0;
    }

    .dp-expense-item .dp-expense-info .type {
        font-weight: 600;
    }

    .dp-expense-item .dp-expense-info .account {
        color: #6c757d;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .dp-empty {
        text-align: center;
        color: #6c757d;
        padding: 3rem 1rem;
    }

    #expenseModal .modal-content {
        max-height: calc(100vh - 1.5rem);
    }

    #expenseModal #expenseForm {
        display: flex;
        flex-direction: column;
        min-height: 0;
        height: 100%;
    }

    #expenseModal .modal-body {
        overflow-y: auto;
        overflow-x: hidden;
    }

    #expenseModal .table-responsive {
        max-height: min(52vh, 34rem);
        overflow: auto;
    }

    #expenseModal .modal-footer,
    #expenseModal .modal-footer > .d-flex {
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .modal-body select.form-select,
    .modal-body input.form-control,
    .modal-body textarea.form-control {
        max-width: 100%;
    }

    .select2-container {
        max-width: 100%;
        width: 100% !important;
    }

    .select2-container .select2-selection--single {
        height: calc(1.5em + 0.75rem + 2px);
        padding: 0.375rem 0.75rem;
        border: 1px solid #ced4da;
        border-radius: 0.375rem;
        display: flex;
        align-items: center;
    }

    .select2-container .select2-selection--single .select2-selection__rendered {
        padding: 0;
        line-height: 1.5;
    }

    .select2-container .select2-selection--single .select2-selection__arrow {
        height: 100%;
        top: 0;
    }

    .select2-container--open .select2-dropdown {
        max-width: calc(100vw - 2rem);
    }

    .select2-results__option {
        font-size: 0.9rem;
        padding: 0.5rem 0.75rem;
    }

    .modal-footer {
        gap: 0.5rem;
    }

    @media (max-width: 575.98px) {
        .modal-footer {
            flex-direction: column;
            align-items: stretch;
        }

        .modal-footer > small {
            text-align: center;
            margin-bottom: 0.25rem;
        }

        .modal-footer > .d-flex {
            flex-direction: column;
            width: 100%;
            gap: 0.5rem;
        }

        .modal-footer .btn {
            width: 100%;
            margin: 0 !important;
        }
    }

    #photoViewModal img {
        max-width: 100%;
        max-height: 75vh;
        border-radius: 8px;
    }
</style>

<body>

<div class="dp-topbar">
    <div class="dp-topbar-row">
        <div class="dp-topbar-logo">
            <img src="<?= base_url() ?>assets/images/logo-dark.png" alt="Avenir Holidays">
        </div>
        <div class="dp-topbar-greeting">
            <?= esc($driver_name ?: 'Driver') ?>
        </div>
        <div class="dp-topbar-title">
            <small><?= date('d M Y') ?></small>
        </div>
        <button type="button" class="dp-logout-btn" id="dpLogoutBtn" title="Logout">
            <i class="bi bi-box-arrow-right"></i>
        </button>
    </div>
</div>

<div class="dp-container">

    <?php if (!empty($upcoming)): ?>
        <div class="dp-search">
            <div class="input-group">
                <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                <input type="text" id="tourSearchInput" class="form-control" placeholder="Search tour, client, vehicle...">
            </div>
        </div>
        <div id="noTourSearchResults" class="alert alert-warning d-none">No tours match your search.</div>

        <?php foreach ($upcoming as $tour): ?>
            <div class="dp-tour-card driver-tour-card"
                data-search="<?= esc(strtolower(($tour['manual_id'] ?? '') . ' ' . ($tour['consignee'] ?? '') . ' ' . ($tour['vehicle_name'] ?? ''))) ?>">

                <div class="dp-tour-head" data-dp-toggle="tour-detail">
                    <div>
                        <div class="dp-tour-id"><?= esc($tour['manual_id']) ?></div>
                        <div class="dp-tour-consignee"><?= esc($tour['consignee']) ?></div>
                    </div>
                    <div class="text-end">
                        <div class="dp-tour-dates"><?= date('d M', strtotime($tour['start_date'])) ?> &rarr; <?= date('d M', strtotime($tour['end_date'])) ?></div>
                        <i class="bi bi-chevron-down dp-tour-caret"></i>
                    </div>
                </div>

                <div class="dp-tour-detail">
                    <div class="dp-tour-detail-row"><span>Vehicle</span><strong><?= esc($tour['vehicle_name'] ?? '-') ?></strong></div>
                    <div class="dp-tour-detail-row"><span>Driver</span><strong><?= esc($tour['allocated_driver_name'] ?? '-') ?></strong></div>
                    <?php
                        $readingsComplete = !empty($tour['readings_complete']);
                        $isExpenseLockedForFinalize = !empty($tour['expense_locked']);
                        $finalizeDisabled = $isExpenseLockedForFinalize || !$readingsComplete;
                        if ($isExpenseLockedForFinalize) {
                            $finalizeTitle = 'Pending expense request already sent';
                        } elseif (!$readingsComplete) {
                            $finalizeTitle = 'Add start & end odometer readings for every day first: missing ' . implode(', ', $tour['missing_reading_days'] ?? []);
                        } else {
                            $finalizeTitle = 'Finalize staged expenses for this tour';
                        }
                    ?>
                    <?php if (!$isExpenseLockedForFinalize && !$readingsComplete): ?>
                        <div class="dp-finalize-hint">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                            Missing readings: <?= esc(implode(', ', $tour['missing_reading_days'] ?? [])) ?>
                        </div>
                    <?php endif; ?>
                    <div class="dp-finalize-row">
                        <small class="text-muted tour-staged-expense-label"
                            data-tour="<?= esc($tour['tour_id']) ?>"
                            data-pending-saved-count="<?= (int) ($tour['pending_expense_count'] ?? 0) ?>">
                            0 staged expense(s)
                        </small>
                        <button
                            type="button"
                            class="btn btn-sm <?= $finalizeDisabled ? 'btn-secondary' : 'btn-outline-success' ?> tour-finalize-expenses-btn"
                            data-tour="<?= esc($tour['tour_id']) ?>"
                            data-manual="<?= esc($tour['manual_id']) ?>"
                            data-consignee="<?= esc($tour['consignee']) ?>"
                            data-driver-name="<?= esc($tour['allocated_driver_name'] ?? '') ?>"
                            data-vehicle-name="<?= esc($tour['vehicle_name'] ?? '') ?>"
                            data-actual-mileage="<?= esc($tour['distance_total'] ?? '') ?>"
                            data-actual-bata="<?= esc($tour['bata_day_rate'] ?? '') ?>"
                            data-no-of-date="<?= esc((string) ($tour['completed_reading_dates_count'] ?? 0)) ?>"
                            data-vehicle-rate="<?= esc($tour['vehicle_rate'] ?? '') ?>"
                            data-locked="<?= $isExpenseLockedForFinalize ? '1' : '0' ?>"
                            data-readings-complete="<?= $readingsComplete ? '1' : '0' ?>"
                            data-pending-saved-count="<?= (int) ($tour['pending_expense_count'] ?? 0) ?>"
                            title="<?= esc($finalizeTitle) ?>"
                            <?= $finalizeDisabled ? 'disabled' : '' ?>>
                            <i class="bi bi-check2-square"></i> Finalize
                        </button>
                    </div>
                </div>

                <div class="dp-tour-body">
                    <?php foreach ($tour['dates'] as $date):
                        $rs = $tour['readings'][$date]['start'] ?? null;
                        $re = $tour['readings'][$date]['end'] ?? null;
                        $isExpenseLocked = !empty($tour['expense_locked']);
                        $savedExpenseItems = $tour['expense_items'][$date] ?? [];
                        $startPhotoUrl = ($rs && !empty($rs['photo'])) ? base_url('uploads/meter_readings/' . $tour['tour_id'] . '/' . $rs['photo']) : '';
                        $endPhotoUrl = ($re && !empty($re['photo'])) ? base_url('uploads/meter_readings/' . $tour['tour_id'] . '/' . $re['photo']) : '';
                    ?>
                        <div class="dp-day-card<?= $date === date('Y-m-d') ? ' dp-day-today' : '' ?>" data-date="<?= esc($date) ?>">
                            <div class="dp-day-date"><?= date('d M Y (D)', strtotime($date)) ?><?= $date === date('Y-m-d') ? ' <span class="dp-today-badge">Today</span>' : '' ?></div>

                            <div class="dp-km-row">
                                <span class="dp-km-label"><i class="bi bi-flag-fill text-success"></i> Start KM</span>
                                <span class="dp-km-value">
                                    <?= $rs ? number_format($rs['km'], 1) : '<span class="text-muted">-</span>' ?>
                                    <?php if ($startPhotoUrl): ?>
                                        <img src="<?= esc($startPhotoUrl) ?>" class="dp-thumb open-photo-view" data-src="<?= esc($startPhotoUrl) ?>" data-label="Start Photo - <?= esc($date) ?>">
                                    <?php endif; ?>
                                </span>
                            </div>

                            <div class="dp-km-row">
                                <span class="dp-km-label"><i class="bi bi-flag-fill text-danger"></i> End KM</span>
                                <span class="dp-km-value">
                                    <?= $re ? number_format($re['km'], 1) : '<span class="text-muted">-</span>' ?>
                                    <?php if ($endPhotoUrl): ?>
                                        <img src="<?= esc($endPhotoUrl) ?>" class="dp-thumb open-photo-view" data-src="<?= esc($endPhotoUrl) ?>" data-label="End Photo - <?= esc($date) ?>">
                                    <?php endif; ?>
                                </span>
                            </div>

                            <div class="dp-km-row">
                                <span class="dp-km-label"><i class="bi bi-signpost-split"></i> Distance</span>
                                <span class="dp-km-value">
                                    <?php if ($rs && $re): ?>
                                        <span class="badge bg-info dp-distance-badge"><?= number_format($re['km'] - $rs['km'], 1) ?> km</span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </span>
                            </div>

                            <div class="dp-actions">
                                <button class="btn btn-success btn-sm open-reading-modal"
                                    data-tour="<?= esc($tour['tour_id']) ?>"
                                    data-date="<?= esc($date) ?>"
                                    data-type="start"
                                    data-km="<?= esc($rs['km'] ?? '') ?>"
                                    data-notes="<?= esc($rs['notes'] ?? '') ?>">
                                    <i class="bi bi-flag-fill"></i> <?= $rs ? 'Edit Start' : 'Start' ?>
                                </button>
                                <button class="btn btn-danger btn-sm open-reading-modal"
                                    data-tour="<?= esc($tour['tour_id']) ?>"
                                    data-date="<?= esc($date) ?>"
                                    data-type="end"
                                    data-km="<?= esc($re['km'] ?? '') ?>"
                                    data-notes="<?= esc($re['notes'] ?? '') ?>">
                                    <i class="bi bi-flag-fill"></i> <?= $re ? 'Edit End' : 'End' ?>
                                </button>
                                <button class="btn btn-sm <?= $isExpenseLocked ? 'btn-secondary' : 'btn-warning' ?> open-expense-modal"
                                    data-tour="<?= esc($tour['tour_id']) ?>"
                                    data-tour-date-id="<?= esc($tour['date_ids'][$date] ?? '') ?>"
                                    data-manual="<?= esc($tour['manual_id']) ?>"
                                    data-consignee="<?= esc($tour['consignee']) ?>"
                                    data-date="<?= esc($date) ?>"
                                    data-driver-name="<?= esc($tour['allocated_driver_name'] ?? '') ?>"
                                    data-locked="<?= $isExpenseLocked ? '1' : '0' ?>"
                                    title="<?= $isExpenseLocked ? 'Pending expense request already sent' : 'Add expense' ?>"
                                    <?= $isExpenseLocked ? 'disabled' : '' ?>>
                                    <i class="bi bi-receipt-cutoff"></i> Expense
                                </button>
                            </div>

                            <button type="button" class="dp-expense-toggle" data-dp-toggle="expense-list">
                                <span><i class="bi bi-receipt"></i> <span class="expense-count-badge-label"><?= count($savedExpenseItems) ?></span> expense(s)</span>
                                <i class="bi bi-chevron-down"></i>
                            </button>
                            <div class="dp-expense-list driver-expense-mini-list"
                                data-tour="<?= esc($tour['tour_id']) ?>" data-date="<?= esc($date) ?>">
                                <?php if (!empty($savedExpenseItems)): ?>
                                    <?php foreach ($savedExpenseItems as $expenseItem): ?>
                                        <div class="dp-expense-item">
                                            <?php if (!empty($expenseItem['invoice_image_src'])): ?>
                                                <img src="<?= esc($expenseItem['invoice_image_src']) ?>" class="dp-thumb open-photo-view" data-src="<?= esc($expenseItem['invoice_image_src']) ?>" data-label="Invoice">
                                            <?php else: ?>
                                                <i class="bi bi-receipt-cutoff text-muted"></i>
                                            <?php endif; ?>
                                            <div class="dp-expense-info">
                                                <div class="type"><?= esc($expenseItem['category_label'] ?? '-') ?></div>
                                                <div class="account"><?= esc($expenseItem['supplier_name'] ?? '-') ?></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-center text-muted small py-2">No expenses</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="dp-empty">
            <i class="bi bi-calendar-x" style="font-size:2rem;"></i>
            <p class="mt-2 mb-0">No current tours.</p>
        </div>
    <?php endif; ?>

</div>

<button type="button" class="dp-today-jump-btn d-none" id="jumpToTodayBtn">
    <i class="bi bi-calendar-check"></i> Today
</button>

<div class="modal fade" id="readingModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="readingModalTitle"><i class="bi bi-speedometer2"></i> Meter Reading</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="mr_tour_id">
                <input type="hidden" id="mr_type">
                <input type="hidden" id="mr_date">

                <div class="mb-3">
                    <label class="form-label fw-bold">KM Reading <font color="#FF0000"><strong>*</strong></font></label>
                    <input type="number" step="0.1" min="0" class="form-control dp-km-input" id="mr_km" placeholder="e.g. 12345.0" inputmode="decimal">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Odometer Photo <font color="#FF0000"><strong>*</strong></font></label>
                    <input type="file" id="mr_photo" accept="image/*" class="d-none">
                    <input type="file" id="mr_photo_camera" accept="image/*" capture="environment" class="d-none">
                    <input type="file" id="mr_photo_gallery" accept="image/*" class="d-none">
                    <div class="dp-photo-picker">
                        <button type="button" class="btn btn-outline-primary dp-photo-picker-btn" id="mr_photo_camera_btn">
                            <i class="bi bi-camera-fill"></i> Take Photo
                        </button>
                        <button type="button" class="btn btn-outline-secondary dp-photo-picker-btn" id="mr_photo_gallery_btn">
                            <i class="bi bi-images"></i> Gallery
                        </button>
                    </div>
                    <div id="mr_photo_preview" class="mt-2"></div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Notes</label>
                    <textarea class="form-control" id="mr_notes" rows="2" placeholder="Optional remarks"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-success" id="save_reading_btn"><i class="bi bi-check-lg"></i> Save</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="tourFinalizeModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tourFinalizeModalTitle"><i class="bi bi-journal-check"></i> Add Logsheet Mileage</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="tf_tour_id">

                <div class="alert alert-info py-2">
                    <div><strong>Tour:</strong> <span id="tf_tour_label">-</span></div>
                    <div><strong>Driver:</strong> <span id="tf_driver_label">-</span></div>
                    <div><strong>Vehicle:</strong> <span id="tf_vehicle_label">-</span></div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Actual Mileage <font color="#FF0000"><strong>*</strong></font></label>
                    <input type="number" step="0.01" min="0" class="form-control tour-finalize-input" id="tf_actual_mileage" placeholder="Enter actual mileage" readonly>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Actual Bata</label>
                    <input type="text" class="form-control tour-finalize-input" id="tf_actual_bata" placeholder="Enter actual bata" readonly>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">No of Date</label>
                    <input type="text" class="form-control tour-finalize-input" id="tf_no_of_date" placeholder="Enter no of date" readonly>
                </div>

                <div class="mb-0">
                    <label class="form-label fw-bold">Vehicle Rate</label>
                    <input type="text" class="form-control tour-finalize-input" id="tf_vehicle_rate" placeholder="Enter vehicle rate" readonly>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-success" id="confirm_tour_finalize_btn"><i class="bi bi-check2-square"></i> Finalize</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="expenseModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="expenseModalTitle"><i class="bi bi-receipt-cutoff"></i> Add Expense</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="expenseForm">
                <div class="modal-body">
                    <input type="hidden" id="ex_tour_id" name="tour_id">
                    <input type="hidden" id="ex_tour_date_id" name="tour_date_id">
                    <input type="hidden" id="ex_edit_expense_id" name="expense_id">
                    <input type="hidden" id="ex_existing_invoice_image_src" value="">

                    <div class="row g-4">
                        <div class="col-lg-5">
                            <div class="alert alert-info py-2">
                                <div><strong>Tour:</strong> <span id="ex_tour_label">-</span></div>

                            </div>

                            <div class="alert alert-warning py-2 d-none" id="expense_edit_mode_notice">
                                Editing a saved expense. Click Update Expense to save changes immediately.
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Expense Date <font color="#FF0000"><strong>*</strong></font></label>
                                <input type="date" class="form-control" id="ex_date" name="expense_date" required>
                            </div>

                            <input type="hidden" id="ex_category" name="category">

                            <div class="mb-3">
                                <label class="form-label fw-bold">Expense Type <font color="#FF0000"><strong>*</strong></font></label>
                                <select class="form-select" id="ex_supplier" name="supplier" required>
                                    <option value="">Select an expense ...</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Invoice / Bill No</label>
                                <input type="text" class="form-control" id="ex_invoice_no" name="invoice_no" placeholder="Enter invoice number">
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Description</label>
                                <textarea class="form-control" id="ex_description" name="description" rows="2" placeholder="Enter expense description"></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Invoice Image</label>
                                <input type="file" id="ex_invoice_image" name="invoice_image" accept="image/*" class="d-none">
                                <input type="file" id="ex_invoice_image_camera" accept="image/*" capture="environment" class="d-none">
                                <input type="file" id="ex_invoice_image_gallery" accept="image/*" class="d-none">
                                <div class="dp-photo-picker">
                                    <button type="button" class="btn btn-outline-primary dp-photo-picker-btn" id="ex_invoice_image_camera_btn">
                                        <i class="bi bi-camera-fill"></i> Take Photo
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary dp-photo-picker-btn" id="ex_invoice_image_gallery_btn">
                                        <i class="bi bi-images"></i> Gallery
                                    </button>
                                </div>
                                <div id="ex_invoice_image_preview" class="mt-2"></div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">LKR Amount <font color="#FF0000"><strong>*</strong></font></label>
                                <input type="number" step="0.01" min="0" class="form-control" id="ex_lkr_amount" name="lkr_amount" placeholder="0.00">
                            </div>
                        </div>

                        <div class="col-lg-7">
                            <div class="card border shadow-sm h-100 mb-0">
                                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>Added Expenses</strong>
                                        <div class="small text-muted">Saved expenses for the selected date appear below.</div>
                                    </div>
                                    <span class="badge bg-dark" id="expense_item_count">0 Items</span>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered align-middle mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>#</th>
                                                    <th>Date</th>
                                                    <th>Type</th>
                                                    <th>Account</th>
                                                    <th>Invoice</th>
                                                    <th>Description</th>
                                                    <th>Image</th>
                                                    <th class="text-end">LKR</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody id="expense_staging_tbody">
                                                <tr>
                                                    <td colspan="9" class="text-center text-muted py-4">No expenses added yet.</td>
                                                </tr>
                                            </tbody>
                                            <tfoot class="table-secondary" id="expense_staging_footer"></tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <small class="text-muted" id="expense_footer_note">Each expense is saved immediately when you click Save.</small>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary d-none" id="cancel_expense_edit_btn">Cancel Edit</button>
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-success" id="save_expense_btn"><i class="bi bi-check2-square"></i> Save</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="photoViewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title" id="photoViewModalLabel">Photo</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <img id="photoViewModalImg" src="" alt="Photo">
            </div>
        </div>
    </div>
</div>

<div id="dpToast" class="dp-toast">
    <i class="bi bi-check-circle-fill"></i>
    <span id="dpToastText">Saved</span>
</div>

<?= view('layouts/inpage_footer') ?>
<?= view('layouts/scripts') ?>

<script>
    $('#dpLogoutBtn').on('click', function () {
        Swal.fire({
            title: 'Logout?',
            text: 'You will need to log in again to continue.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#764ba2',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, Logout'
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }

            $.ajax({
                url: '<?= base_url('logout') ?>',
                method: 'GET',
                dataType: 'json',
                success: function () {
                    window.location.href = '<?= base_url() ?>';
                },
                error: function () {
                    window.location.href = '<?= base_url() ?>';
                }
            });
        });
    });

    function wirePhotoPicker(canonicalInputId, cameraInputId, galleryInputId, cameraBtnId, galleryBtnId) {
        const canonicalInput = document.getElementById(canonicalInputId);
        const cameraInput = document.getElementById(cameraInputId);
        const galleryInput = document.getElementById(galleryInputId);

        function adoptFile(sourceInput) {
            const file = sourceInput.files[0];
            if (!file) {
                return;
            }

            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);
            canonicalInput.files = dataTransfer.files;
            sourceInput.value = '';
            $(canonicalInput).trigger('change');
        }

        $('#' + cameraBtnId).on('click', function () {
            cameraInput.click();
        });

        $('#' + galleryBtnId).on('click', function () {
            galleryInput.click();
        });

        cameraInput.addEventListener('change', function () {
            adoptFile(cameraInput);
        });

        galleryInput.addEventListener('change', function () {
            adoptFile(galleryInput);
        });
    }

    wirePhotoPicker('mr_photo', 'mr_photo_camera', 'mr_photo_gallery', 'mr_photo_camera_btn', 'mr_photo_gallery_btn');
    wirePhotoPicker('ex_invoice_image', 'ex_invoice_image_camera', 'ex_invoice_image_gallery', 'ex_invoice_image_camera_btn', 'ex_invoice_image_gallery_btn');

    let dpToastTimer = null;

    function hapticFeedback() {
        if (window.navigator && typeof window.navigator.vibrate === 'function') {
            window.navigator.vibrate(35);
        }
    }

    function showSuccessToast(message) {
        hapticFeedback();

        const toast = $('#dpToast');
        $('#dpToastText').text(message || 'Saved');

        clearTimeout(dpToastTimer);
        toast.addClass('dp-toast-show');
        dpToastTimer = setTimeout(function () {
            toast.removeClass('dp-toast-show');
        }, 2000);
    }

    $('#tourSearchInput').on('input', function () {
        const term = $(this).val().trim().toLowerCase();
        let visibleCount = 0;
        $('.driver-tour-card').each(function () {
            const matches = !term || ($(this).data('search') || '').toString().includes(term);
            $(this).toggleClass('d-none', !matches);
            if (matches) visibleCount++;
        });
        $('#noTourSearchResults').toggleClass('d-none', visibleCount !== 0);
    });

    $(document).on('click', '[data-dp-toggle="tour-detail"]', function () {
        const card = $(this).closest('.dp-tour-card');
        card.find('.dp-tour-detail').toggleClass('show');
        card.find('.dp-tour-body').toggleClass('collapsed');
        $(this).find('.dp-tour-caret').toggleClass('bi-chevron-down bi-chevron-up');
    });

    $(document).on('click', '[data-dp-toggle="expense-list"]', function () {
        $(this).next('.dp-expense-list').toggleClass('show');
        $(this).find('.bi-chevron-down, .bi-chevron-up').toggleClass('bi-chevron-down bi-chevron-up');
    });

    $(document).on('click', '.open-photo-view', function (e) {
        e.stopPropagation();
        const src = $(this).data('src');
        if (!src) return;
        $('#photoViewModalImg').attr('src', src);
        $('#photoViewModalLabel').text($(this).data('label') || 'Photo');
        $('#photoViewModal').modal('show');
    });

    const expenseAccountsByCategory = <?= json_encode($expense_accounts_by_category ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const expenseCategoryLabels = <?= json_encode($expense_categories ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const expenseAccountCategoryByCode = {};
    Object.keys(expenseAccountsByCategory).forEach(function (categoryCode) {
        (expenseAccountsByCategory[categoryCode] || []).forEach(function (account) {
            expenseAccountCategoryByCode[account.L_Code] = categoryCode;
        });
    });
    const savedExpensesState = { tourId: '', expenseDate: '', items: [], loading: false, error: '' };
    let savedExpensesRequestId = 0;

    function buildExpenseAccountSelect() {
        const supplierSelect = $('#ex_supplier');
        supplierSelect.html('');

        Object.keys(expenseAccountsByCategory).forEach(function (categoryCode) {
            const accounts = expenseAccountsByCategory[categoryCode] || [];
            if (!accounts.length) {
                return;
            }

            const group = $('<optgroup>', { label: expenseCategoryLabels[categoryCode] || categoryCode });
            accounts.forEach(function (account) {
                group.append(
                    $('<option>', {
                        value: account.L_Code || '',
                        text: account.PName || account.L_Code || ''
                    })
                );
            });
            supplierSelect.append(group);
        });

        if (supplierSelect.hasClass('select2-hidden-accessible')) {
            supplierSelect.select2('destroy');
        }
        supplierSelect.select2({
            theme: 'default',
            dropdownParent: $('#expenseModal'),
            placeholder: 'Search an expense account...',
            allowClear: true,
            width: '100%'
        }).val('').trigger('change');
    }

    function selectExpenseAccount(supplierCode) {
        $('#ex_supplier').val(supplierCode || '').trigger('change');
    }

    $('#ex_supplier').on('change', function () {
        const supplierCode = $(this).val() || '';
        $('#ex_category').val(expenseAccountCategoryByCode[supplierCode] || '');
    });

    function escapeHtml(value) {
        return $('<div>').text(value == null ? '' : value).html();
    }

    function renderExpenseImagePreview(imageSrc = '') {
        if (!imageSrc) {
            $('#ex_invoice_image_preview').html('');
            return;
        }

        $('#ex_invoice_image_preview').html(
            '<a href="' + imageSrc + '" target="_blank">' +
                '<img src="' + imageSrc + '" style="max-width:120px;max-height:100px;border-radius:6px;">' +
            '</a>'
        );
    }

    function getCurrentExpenseTourId() {
        return $('#ex_tour_id').val() || '';
    }

    function getCurrentExpenseDate() {
        return $('#ex_date').val() || '';
    }

    function getCurrentTourFinalizeTourId() {
        return $('#tf_tour_id').val() || '';
    }

    function isExpenseEditMode() {
        return $('#ex_edit_expense_id').val() !== '';
    }

    function resetTourFinalizeModalFields() {
        $('#tf_tour_id').val('');
        $('#tf_tour_label').text('-');
        $('#tf_driver_label').text('-');
        $('#tf_vehicle_label').text('-');
        $('#tf_actual_mileage').val('');
        $('#tf_actual_bata').val('');
        $('#tf_no_of_date').val('');
        $('#tf_vehicle_rate').val('');
        $('#confirm_tour_finalize_btn').prop('disabled', false).html('<i class="bi bi-check2-square"></i> Finalize');
        $('.tour-finalize-input').prop('disabled', false);
    }

    function openTourFinalizeModal(button) {
        const actualMileage = button.attr('data-actual-mileage') || '';
        const actualBata = button.attr('data-actual-bata') || '';
        const noOfDate = button.attr('data-no-of-date') || '';
        const vehicleRate = button.attr('data-vehicle-rate') || '';

        $('#tf_tour_id').val(button.data('tour') || '');
        $('#tf_tour_label').text((button.data('manual') || '-') + ' - ' + (button.data('consignee') || '-'));
        $('#tf_driver_label').text(button.data('driverName') || '-');
        $('#tf_vehicle_label').text(button.data('vehicleName') || '-');
        $('#tf_actual_mileage').val(actualMileage);
        $('#tf_actual_bata').val(actualBata);
        $('#tf_no_of_date').val(noOfDate);
        $('#tf_vehicle_rate').val(vehicleRate);
        $('#confirm_tour_finalize_btn').prop('disabled', false).html('<i class="bi bi-check2-square"></i> Finalize');
        $('.tour-finalize-input').prop('disabled', false);
        $('#tourFinalizeModal').modal('show');
    }

    function updateExpenseModalTitle() {
        const titlePrefix = isExpenseEditMode() ? 'Edit Expense' : 'Add Expense';
        const expenseDate = getCurrentExpenseDate();
        $('#expenseModalTitle').html('<i class="bi bi-receipt-cutoff"></i> ' + titlePrefix + (expenseDate ? ' - ' + expenseDate : ''));
    }

    function setExpenseFormMode(mode = 'create') {
        const isEditMode = mode === 'edit';

        if (!isEditMode) {
            $('#ex_edit_expense_id').val('');
            $('#ex_existing_invoice_image_src').val('');
        }

        $('#expense_edit_mode_notice').toggleClass('d-none', !isEditMode);
        $('#cancel_expense_edit_btn').toggleClass('d-none', !isEditMode);
        $('#save_expense_btn')
            .removeClass('btn-success btn-primary')
            .addClass(isEditMode ? 'btn-primary' : 'btn-success')
            .html(isEditMode ? '<i class="bi bi-pencil-square"></i> Update Expense' : '<i class="bi bi-check2-square"></i> Save');
        $('#expense_footer_note').text(
            isEditMode
                ? 'Saved expense changes will be updated immediately.'
                : 'Each expense is saved immediately when you click Save.'
        );

        updateExpenseModalTitle();
    }

    function setSavedExpensesState(tourId, expenseDate, items = [], loading = false, error = '') {
        savedExpensesState.tourId = String(tourId || '');
        savedExpensesState.expenseDate = String(expenseDate || '');
        savedExpensesState.items = Array.isArray(items) ? items : [];
        savedExpensesState.loading = Boolean(loading);
        savedExpensesState.error = String(error || '');
    }

    function getSavedExpensesForCurrentSelection() {
        if (
            String(savedExpensesState.tourId) !== String(getCurrentExpenseTourId()) ||
            String(savedExpensesState.expenseDate) !== String(getCurrentExpenseDate())
        ) {
            return [];
        }

        return savedExpensesState.items;
    }

    function findSavedExpenseById(expenseId) {
        const normalizedId = String(expenseId || '');
        return getSavedExpensesForCurrentSelection().find(function (expense) {
            return String(expense.id) === normalizedId;
        }) || null;
    }

    function populateSavedExpenseForEdit(expense) {
        if (!expense) {
            return;
        }

        $('#ex_edit_expense_id').val(String(expense.id || ''));
        $('#ex_tour_id').val(expense.tour_id || getCurrentExpenseTourId());
        $('#ex_tour_date_id').val(expense.tour_date_id || '');
        $('#ex_date').val(expense.expense_date || '');
        $('#ex_category').val(expense.category || '');
        selectExpenseAccount(expense.supplier || '');
        $('#ex_invoice_no').val(expense.invoice_no || '');
        $('#ex_description').val(expense.description || '');
        $('#ex_lkr_amount').val(Number(expense.lkr_amount || 0).toFixed(2));
        $('#ex_invoice_image').val('');
        $('#ex_existing_invoice_image_src').val(expense.invoice_image_src || '');
        renderExpenseImagePreview(expense.invoice_image_src || '');
        setExpenseFormMode('edit');
        $('#ex_invoice_no').focus();
    }

    function loadSavedExpenses(tourId, expenseDate) {
        const normalizedTourId = String(tourId || '');
        const normalizedExpenseDate = String(expenseDate || '');
        const requestId = ++savedExpensesRequestId;

        setSavedExpensesState(normalizedTourId, normalizedExpenseDate, [], true, '');
        renderExpenseTable();

        if (!normalizedTourId || !normalizedExpenseDate) {
            setSavedExpensesState(normalizedTourId, normalizedExpenseDate, [], false, '');
            renderExpenseTable();
            return;
        }

        $.ajax({
            url: '<?= base_url('driver_portal/get_expenses') ?>',
            type: 'POST',
            dataType: 'json',
            data: {
                tour_id: normalizedTourId,
                expense_date: normalizedExpenseDate
            },
            success: function (res) {
                if (requestId !== savedExpensesRequestId) {
                    return;
                }

                if (res.status === 200) {
                    setSavedExpensesState(normalizedTourId, normalizedExpenseDate, res.data || [], false, '');
                    renderDashboardExpenseSummary(normalizedTourId, normalizedExpenseDate, res.data || []);
                } else {
                    setSavedExpensesState(normalizedTourId, normalizedExpenseDate, [], false, res.message || 'Failed to load saved expenses.');
                }

                renderExpenseTable();
            },
            error: function () {
                if (requestId !== savedExpensesRequestId) {
                    return;
                }

                setSavedExpensesState(normalizedTourId, normalizedExpenseDate, [], false, 'Failed to load saved expenses.');
                renderExpenseTable();
            }
        });
    }

    function getSavedExpenseCountBadge(tourId, expenseDate) {
        return $('.expense-count-badge').filter(function () {
            return String($(this).data('tour')) === String(tourId) && String($(this).data('date')) === String(expenseDate);
        }).first();
    }

    function getDashboardExpenseListBlock(tourId, expenseDate) {
        return $('.driver-expense-mini-list').filter(function () {
            return String($(this).data('tour')) === String(tourId) && String($(this).data('date')) === String(expenseDate);
        }).first();
    }

    function renderDashboardExpenseSummary(tourId, expenseDate, expenses) {
        const block = getDashboardExpenseListBlock(tourId, expenseDate);

        if (!block.length) {
            return;
        }

        const toggle = block.prev('.dp-expense-toggle');
        toggle.find('.expense-count-badge-label').text(Array.isArray(expenses) ? expenses.length : 0);

        if (!Array.isArray(expenses) || expenses.length === 0) {
            block.html('<div class="text-center text-muted small py-2">No expenses</div>');
            return;
        }

        let rows = '';

        expenses.forEach(function (expense) {
            rows +=
                '<div class="dp-expense-item">' +
                    (expense.invoice_image_src
                        ? '<img src="' + expense.invoice_image_src + '" class="dp-thumb open-photo-view" data-src="' + expense.invoice_image_src + '" data-label="Invoice">'
                        : '<i class="bi bi-receipt-cutoff text-muted"></i>') +
                    '<div class="dp-expense-info">' +
                        '<div class="type">' + escapeHtml(expense.category_label || '-') + '</div>' +
                        '<div class="account">' + escapeHtml(expense.supplier_name || '-') + '</div>' +
                    '</div>' +
                '</div>';
        });

        block.html(rows);
    }

    function setSavedExpenseCountBadge(tourId, expenseDate, nextCount) {
        const safeCount = Math.max(0, Number(nextCount) || 0);
        const block = getDashboardExpenseListBlock(tourId, expenseDate);
        if (block.length) {
            block.prev('.dp-expense-toggle').find('.expense-count-badge-label').text(String(safeCount));
        }
    }

    function changeSavedExpenseCount(tourId, expenseDate, delta) {
        if (!expenseDate) {
            return;
        }

        const block = getDashboardExpenseListBlock(tourId, expenseDate);
        if (!block.length) {
            return;
        }

        const label = block.prev('.dp-expense-toggle').find('.expense-count-badge-label');
        const currentCount = Number(label.text() || 0);
        setSavedExpenseCountBadge(tourId, expenseDate, currentCount + delta);
    }

    function incrementSavedExpenseCounts(tourId, expenses) {
        const countsByDate = {};

        expenses.forEach(function (expense) {
            const expenseDate = String(expense.expense_date || '');
            if (!expenseDate) {
                return;
            }

            countsByDate[expenseDate] = (countsByDate[expenseDate] || 0) + 1;
        });

        Object.keys(countsByDate).forEach(function (expenseDate) {
            changeSavedExpenseCount(tourId, expenseDate, countsByDate[expenseDate]);
        });
    }

    function getTourFinalizeButtons(tourId) {
        return $('.tour-finalize-expenses-btn').filter(function () {
            return String($(this).data('tour')) === String(tourId);
        });
    }

    function getTourExpenseActionButtons(tourId) {
        return $('.open-expense-modal').filter(function () {
            return String($(this).data('tour')) === String(tourId);
        });
    }

    function getPendingSavedExpenseCount(tourId) {
        const button = getTourFinalizeButtons(tourId).first();
        return Number(button.attr('data-pending-saved-count') || 0);
    }

    function setPendingSavedExpenseCount(tourId, count) {
        const normalizedCount = Math.max(0, Number(count) || 0);

        getTourFinalizeButtons(tourId).attr('data-pending-saved-count', String(normalizedCount));
        $('.tour-staged-expense-label').filter(function () {
            return String($(this).data('tour')) === String(tourId);
        }).attr('data-pending-saved-count', String(normalizedCount));
    }

    function setTourExpenseLockState(tourId, isLocked) {
        const lockedValue = isLocked ? '1' : '0';
        const expenseButtons = getTourExpenseActionButtons(tourId);

        getTourFinalizeButtons(tourId)
            .attr('data-locked', lockedValue)
            .data('locked', isLocked ? 1 : 0)
            .prop('disabled', isLocked);

        expenseButtons.each(function () {
            const button = $(this);
            button
                .attr('data-locked', lockedValue)
                .data('locked', isLocked ? 1 : 0)
                .prop('disabled', isLocked)
                .removeClass('btn-warning btn-secondary')
                .addClass(isLocked ? 'btn-secondary' : 'btn-warning')
                .attr('title', isLocked ? 'Pending expense request already sent' : 'Add expense');
        });
    }

    function updateTourFinalizeButtons(tourId = null) {
        const buttons = tourId ? getTourFinalizeButtons(tourId) : $('.tour-finalize-expenses-btn');

        buttons.each(function () {
            const button = $(this);
            const currentTourId = String(button.data('tour'));
            const isLocked = Number(button.data('locked')) === 1;
            const pendingSavedCount = Number(button.attr('data-pending-saved-count') || 0);
            const label = $('.tour-staged-expense-label').filter(function () {
                return String($(this).data('tour')) === currentTourId;
            }).first();

            if (label.length) {
                label.text(pendingSavedCount + ' expense(s) saved');
            }

            button
                .prop('disabled', isLocked)
                .removeClass('btn-outline-success btn-success btn-secondary')
                .addClass(isLocked ? 'btn-secondary' : (pendingSavedCount > 0 ? 'btn-success' : 'btn-outline-success'))
                .html('<i class="bi bi-check2-square"></i> Finalize' + (pendingSavedCount > 0 ? ' (' + pendingSavedCount + ')' : ''));
        });
    }

    function setTourFinalizeLoadingState(tourId, isLoading) {
        const topButtons = getTourFinalizeButtons(tourId);
        const isCurrentFinalizeTour = String(getCurrentTourFinalizeTourId()) === String(tourId);

        topButtons
            .prop('disabled', isLoading)
            .html(isLoading ? '<i class="bi bi-hourglass"></i> Finalizing...' : '<i class="bi bi-check2-square"></i> Finalize');

        if (isCurrentFinalizeTour) {
            $('#confirm_tour_finalize_btn')
                .prop('disabled', isLoading)
                .html(isLoading ? '<i class="bi bi-hourglass"></i> Finalizing...' : '<i class="bi bi-check2-square"></i> Finalize');
            $('.tour-finalize-input').prop('disabled', isLoading);
        }
    }

    function runTourFinalizeRequest(tourId) {
        setTourFinalizeLoadingState(tourId, true);

        submitTourFinalize(tourId).then(function (res) {
            setPendingSavedExpenseCount(tourId, 0);
            setTourExpenseLockState(tourId, true);
            renderExpenseTable();
            updateTourFinalizeButtons(tourId);
            $('#expenseModal').modal('hide');
            $('#tourFinalizeModal').modal('hide');
            Swal.fire({
                icon: 'success',
                title: 'Finalized',
                text: res.message || 'Tour finalized successfully.',
                showCancelButton: !!res.finalize_id,
                confirmButtonText: res.finalize_id ? 'View Settlement Invoice' : 'OK',
                cancelButtonText: 'Close'
            }).then(function (result) {
                if (result.isConfirmed && res.finalize_id) {
                    window.open('<?= base_url('driver_portal/settlement_invoice/') ?>' + res.finalize_id, '_blank');
                }
                window.location.reload();
            });
        }).catch(function (error) {
            Swal.fire({ icon: 'error', title: 'Error', text: error.message || 'Failed to finalize tour.' });
        }).finally(function () {
            setTourFinalizeLoadingState(tourId, false);
        });
    }

    function finalizeTourExpenses(tourId, skipConfirm = false) {
        if (!tourId) {
            Swal.fire({ icon: 'warning', title: 'Missing', text: 'Tour details could not be loaded.' });
            return;
        }

        if (skipConfirm) {
            runTourFinalizeRequest(tourId);
            return;
        }

        const pendingSavedCount = getPendingSavedExpenseCount(tourId);

        Swal.fire({
            icon: 'question',
            title: 'Finalize expenses?',
            text: pendingSavedCount > 0
                ? ('This will finalize ' + pendingSavedCount + ' saved expense(s).')
                : 'This will finalize this tour without expenses.',
            showCancelButton: true,
            confirmButtonText: 'Finalize',
            confirmButtonColor: '#78b848'
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }

            runTourFinalizeRequest(tourId);
        });
    }

    function resetExpenseEntryFields(keepDate = true) {
        const currentDate = $('#ex_date').val();

        $('#ex_category').val('');
        selectExpenseAccount('');
        $('#ex_invoice_no').val('');
        $('#ex_description').val('');
        $('#ex_invoice_image').val('');
        $('#ex_edit_expense_id').val('');
        $('#ex_existing_invoice_image_src').val('');
        renderExpenseImagePreview('');
        $('#ex_lkr_amount').val('');

        if (keepDate) {
            $('#ex_date').val(currentDate);
        }

        setExpenseFormMode('create');
    }

    function renderExpenseTable() {
        const savedExpenses = getSavedExpensesForCurrentSelection();
        const tbody = $('#expense_staging_tbody');
        const footer = $('#expense_staging_footer');
        let totalLkr = 0;

        $('#expense_item_count').text(savedExpenses.length + ' Saved');

        if (savedExpensesState.loading && savedExpenses.length === 0) {
            tbody.html('<tr><td colspan="9" class="text-center text-muted py-4">Loading saved expenses...</td></tr>');
            footer.html('');
            return;
        }

        if (savedExpensesState.error && savedExpenses.length === 0) {
            tbody.html('<tr><td colspan="9" class="text-center text-danger py-4">' + escapeHtml(savedExpensesState.error) + '</td></tr>');
            footer.html('');
            return;
        }

        if (savedExpenses.length === 0) {
            tbody.html('<tr><td colspan="9" class="text-center text-muted py-4">No expenses added yet.</td></tr>');
            footer.html('');
            return;
        }

        tbody.html('');
        let rowIndex = 1;

        savedExpenses.forEach(function (expense) {
            totalLkr += Number(expense.lkr_amount) || 0;

            tbody.append(
                '<tr class="table-success-subtle">' +
                    '<td>' + rowIndex + '</td>' +
                    '<td>' + escapeHtml(expense.expense_date) + '</td>' +
                    '<td>' + escapeHtml(expense.category_label) + '</td>' +
                    '<td>' + escapeHtml(expense.supplier_name) + '</td>' +
                    '<td>' + escapeHtml(expense.invoice_no) + '</td>' +
                    '<td>' + escapeHtml(expense.description || '-') + '</td>' +
                    '<td>' + (expense.invoice_image_src ? '<a href="' + expense.invoice_image_src + '" target="_blank"><img src="' + expense.invoice_image_src + '" style="width:52px;height:52px;object-fit:cover;border-radius:6px;border:1px solid #dee2e6;"></a>' : '<span class="text-muted">-</span>') + '</td>' +
                    '<td class="text-end">' + Number(expense.lkr_amount).toFixed(2) + '</td>' +
                    '<td>' +
                        (expense.can_edit
                            ? '<div class="d-flex gap-1 flex-wrap"><button type="button" class="btn btn-sm btn-outline-primary edit-saved-expense" data-id="' + escapeHtml(expense.id) + '"><i class="bi bi-pencil-square"></i></button><button type="button" class="btn btn-sm btn-outline-danger delete-saved-expense" data-id="' + escapeHtml(expense.id) + '"><i class="bi bi-trash"></i></button></div>'
                            : '<span class="text-muted">-</span>'
                        ) +
                    '</td>' +
                '</tr>'
            );

            rowIndex++;
        });

        footer.html(
            '<tr>' +
                '<th colspan="7" class="text-end">Total</th>' +
                '<th class="text-end">LKR ' + totalLkr.toFixed(2) + '</th>' +
                '<th></th>' +
            '</tr>'
        );
    }

    function uploadExpense(payload, invoiceImage) {
        return new Promise(function (resolve, reject) {
            const fd = new FormData();

            fd.append('tour_id', payload.tour_id);
            fd.append('tour_date_id', payload.tour_date_id || '');
            fd.append('expense_date', payload.expense_date || '');
            fd.append('category', payload.category || '');
            fd.append('supplier', payload.supplier || '');
            fd.append('invoice_no', payload.invoice_no || '');
            fd.append('description', payload.description || '');
            fd.append('lkr_amount', String(payload.lkr_amount || 0));

            if (invoiceImage) {
                fd.append('invoice_image', invoiceImage);
            }

            $.ajax({
                url: '<?= base_url('driver_portal/save_expense') ?>',
                type: 'POST',
                data: fd,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function (res) {
                    if (res.status === 200) {
                        resolve(res);
                    } else {
                        reject(new Error(res.message || 'Failed to save expense.'));
                    }
                },
                error: function () {
                    reject(new Error('Something went wrong while saving the expense.'));
                }
            });
        });
    }

    function submitTourFinalize(tourId) {
        return new Promise(function (resolve, reject) {
            $.ajax({
                url: '<?= base_url('driver_portal/tour_finalize') ?>',
                type: 'POST',
                dataType: 'json',
                data: {
                    tour_id: tourId,
                    actual_mileage: $('#tf_actual_mileage').val(),
                    actual_bata: $('#tf_actual_bata').val(),
                    no_of_date: $('#tf_no_of_date').val(),
                    vehicle_rate: $('#tf_vehicle_rate').val()
                },
                success: function (res) {
                    if (res.status === 200) {
                        resolve(res);
                    } else {
                        reject(new Error(res.message || 'Failed to finalize tour.'));
                    }
                },
                error: function () {
                    reject(new Error('Something went wrong while finalizing the tour.'));
                }
            });
        });
    }

    function updateSavedExpense(expenseId, payload, invoiceImage) {
        return new Promise(function (resolve, reject) {
            const fd = new FormData();

            fd.append('expense_id', String(expenseId || ''));
            fd.append('tour_id', payload.tour_id || '');
            fd.append('tour_date_id', payload.tour_date_id || '');
            fd.append('expense_date', payload.expense_date || '');
            fd.append('category', payload.category || '');
            fd.append('supplier', payload.supplier || '');
            fd.append('invoice_no', payload.invoice_no || '');
            fd.append('description', payload.description || '');
            fd.append('lkr_amount', String(payload.lkr_amount || 0));

            if (invoiceImage) {
                fd.append('invoice_image', invoiceImage);
            }

            $.ajax({
                url: '<?= base_url('driver_portal/update_expense') ?>',
                type: 'POST',
                data: fd,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function (res) {
                    if (res.status === 200) {
                        resolve(res);
                    } else {
                        reject(new Error(res.message || 'Failed to update expense.'));
                    }
                },
                error: function () {
                    reject(new Error('Something went wrong while updating the expense.'));
                }
            });
        });
    }

    function deleteSavedExpense(expenseId, tourId) {
        return new Promise(function (resolve, reject) {
            $.ajax({
                url: '<?= base_url('driver_portal/delete_expense') ?>',
                type: 'POST',
                dataType: 'json',
                data: {
                    expense_id: String(expenseId || ''),
                    tour_id: String(tourId || '')
                },
                success: function (res) {
                    if (res.status === 200) {
                        resolve(res);
                    } else {
                        reject(new Error(res.message || 'Failed to delete expense.'));
                    }
                },
                error: function () {
                    reject(new Error('Something went wrong while deleting the expense.'));
                }
            });
        });
    }

    $(document).on('click', '.open-reading-modal', function (e) {
        e.stopPropagation();
        const type = $(this).data('type');

        $('#mr_tour_id').val($(this).data('tour'));
        $('#mr_date').val($(this).data('date'));
        $('#mr_type').val(type);
        $('#mr_km').val($(this).data('km') || '');
        $('#mr_notes').val($(this).data('notes') || '');
        $('#mr_photo').val('');
        $('#mr_photo_preview').html('');
        $('#readingModalTitle').html('<i class="bi bi-speedometer2"></i> ' + (type === 'start' ? 'Start' : 'End') + ' Meter Reading - ' + $(this).data('date'));
        $('#readingModal').modal('show');
    });

    $(document).on('click', '.open-expense-modal', function (e) {
        e.stopPropagation();
        const button = $(this);

        if (Number(button.data('locked')) === 1) {
            Swal.fire({ icon: 'warning', title: 'Locked', text: 'A pending expense request has already been sent for this tour.' });
            return;
        }

        $('#expenseForm')[0].reset();
        selectExpenseAccount('');
        $('#ex_tour_id').val(button.data('tour'));
        $('#ex_tour_date_id').val(button.data('tourDateId') || '');
        $('#ex_date').val(button.data('date'));
        $('#ex_tour_label').text((button.data('manual') || '-') + ' - ' + (button.data('consignee') || '-'));
        $('#ex_driver_label').text(button.data('driverName') || '-');
        $('#expenseModalTitle').html('<i class="bi bi-receipt-cutoff"></i> Add Expense - ' + button.data('date'));
        resetExpenseEntryFields(true);
        renderExpenseTable();
        loadSavedExpenses(button.data('tour'), button.data('date'));
        updateTourFinalizeButtons(button.data('tour'));
        $('#expenseModal').modal('show');
    });

    $('#ex_date').on('change', function () {
        $('#ex_tour_date_id').val('');
        updateExpenseModalTitle();
        loadSavedExpenses(getCurrentExpenseTourId(), $(this).val());
    });

    $('#ex_invoice_image').on('change', function () {
        const file = this.files[0];

        if (!file) {
            renderExpenseImagePreview($('#ex_existing_invoice_image_src').val());
            return;
        }

        const reader = new FileReader();
        reader.onload = function (e) {
            renderExpenseImagePreview(e.target.result || '');
        };
        reader.readAsDataURL(file);
    });

    $(document).on('click', '.edit-saved-expense', function () {
        const expense = findSavedExpenseById($(this).data('id'));

        if (!expense) {
            Swal.fire({ icon: 'error', title: 'Not Found', text: 'The selected expense could not be loaded.' });
            return;
        }

        populateSavedExpenseForEdit(expense);
    });

    $(document).on('click', '.delete-saved-expense', function () {
        const expense = findSavedExpenseById($(this).data('id'));

        if (!expense) {
            Swal.fire({ icon: 'error', title: 'Not Found', text: 'The selected expense could not be loaded.' });
            return;
        }

        Swal.fire({
            icon: 'warning',
            title: 'Delete expense?',
            text: 'This saved expense will be removed permanently.',
            showCancelButton: true,
            confirmButtonText: 'Delete',
            confirmButtonColor: '#dc3545'
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }

            deleteSavedExpense(expense.id, expense.tour_id || getCurrentExpenseTourId()).then(function (res) {
                if (String($('#ex_edit_expense_id').val() || '') === String(expense.id || '')) {
                    resetExpenseEntryFields(true);
                }

                changeSavedExpenseCount(expense.tour_id, expense.expense_date, -1);
                setPendingSavedExpenseCount(expense.tour_id, getPendingSavedExpenseCount(expense.tour_id) - 1);
                loadSavedExpenses(expense.tour_id, expense.expense_date);
                updateTourFinalizeButtons(expense.tour_id);

                showSuccessToast(res.message || 'Expense deleted.');
            }).catch(function (error) {
                Swal.fire({ icon: 'error', title: 'Error', text: error.message || 'Failed to delete expense.' });
            });
        });
    });

    $('#cancel_expense_edit_btn').on('click', function () {
        resetExpenseEntryFields(true);
        loadSavedExpenses(getCurrentExpenseTourId(), getCurrentExpenseDate());
        $('#ex_invoice_no').focus();
    });

    $('#mr_photo').on('change', function () {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function (e) {
                $('#mr_photo_preview').html('<img src="' + e.target.result + '" style="max-width:120px;max-height:100px;border-radius:6px;">');
            };
            reader.readAsDataURL(file);
        }
    });

    $('#save_reading_btn').click(function () {
        const km = $('#mr_km').val();
        const photo = $('#mr_photo')[0].files[0];

        if (!km) {
            Swal.fire({ icon: 'error', title: 'Missing', text: 'KM reading required.' });
            return;
        }

        if (!photo) {
            Swal.fire({ icon: 'error', title: 'Missing', text: 'Odometer photo required.' });
            return;
        }

        const btn = $(this).prop('disabled', true).html('<i class="bi bi-hourglass"></i> Saving...');
        const fd = new FormData();

        fd.append('tour_id', $('#mr_tour_id').val());
        fd.append('reading_date', $('#mr_date').val());
        fd.append('type', $('#mr_type').val());
        fd.append('km', km);
        fd.append('notes', $('#mr_notes').val());
        fd.append('photo', photo);

        $.ajax({
            url: '<?= base_url('driver_portal/save_meter_reading') ?>',
            type: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (res) {
                if (res.status === 200) {
                    showSuccessToast('Reading saved!');
                    setTimeout(function () {
                        location.reload();
                    }, 900);
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: res.message });
                    btn.prop('disabled', false).html('<i class="bi bi-check-lg"></i> Save');
                }
            },
            error: function () {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Something went wrong!' });
                btn.prop('disabled', false).html('<i class="bi bi-check-lg"></i> Save');
            }
        });
    });

    $(document).on('click', '.tour-finalize-expenses-btn', function (e) {
        e.stopPropagation();
        const button = $(this);

        if (Number(button.data('locked')) === 1) {
            Swal.fire({ icon: 'warning', title: 'Locked', text: 'A pending expense request has already been sent for this tour.' });
            return;
        }

        if (Number(button.data('readingsComplete')) !== 1) {
            Swal.fire({ icon: 'warning', title: 'Readings Missing', text: button.attr('title') || 'Add start & end odometer readings for every day of the tour before finalizing.' });
            return;
        }

        openTourFinalizeModal(button);
    });

    $('#confirm_tour_finalize_btn').on('click', function () {
        const tourId = getCurrentTourFinalizeTourId();
        const actualMileage = $('#tf_actual_mileage').val().trim();

        if (!tourId) {
            Swal.fire({ icon: 'error', title: 'Missing', text: 'Tour details could not be loaded.' });
            return;
        }

        if (!actualMileage) {
            Swal.fire({ icon: 'error', title: 'Missing', text: 'Actual Mileage is required.' });
            $('#tf_actual_mileage').trigger('focus');
            return;
        }

        const totalExpenseCount = getPendingSavedExpenseCount(tourId);

        if (totalExpenseCount === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'No Expenses Added',
                text: 'No expenses have been added for this tour. Finalize anyway?',
                showCancelButton: true,
                confirmButtonText: 'Finalize Anyway',
                confirmButtonColor: '#78b848'
            }).then(function (result) {
                if (result.isConfirmed) {
                    finalizeTourExpenses(tourId, true);
                }
            });
            return;
        }

        finalizeTourExpenses(tourId, true);
    });

    $('#tourFinalizeModal').on('hidden.bs.modal', function () {
        resetTourFinalizeModalFields();
    });

    $('#tourFinalizeModal').on('shown.bs.modal', function () {
        $('#tf_actual_mileage').trigger('focus');
    });

    $('#expenseForm').on('submit', function (e) {
        e.preventDefault();

        const payload = {
            tour_id: $('#ex_tour_id').val(),
            tour_date_id: $('#ex_tour_date_id').val(),
            expense_date: $('#ex_date').val(),
            category: $('#ex_category').val(),
            supplier: $('#ex_supplier').val(),
            invoice_no: $('#ex_invoice_no').val().trim(),
            description: $('#ex_description').val().trim(),
            lkr_amount: $('#ex_lkr_amount').val()
        };
        const invoiceImage = $('#ex_invoice_image')[0].files[0] || null;

        if (!payload.tour_id || !payload.expense_date || !payload.category || !payload.supplier) {
            Swal.fire({ icon: 'error', title: 'Missing', text: 'Please fill all required fields.' });
            return;
        }

        if (!parseFloat(payload.lkr_amount)) {
            Swal.fire({ icon: 'error', title: 'Missing', text: 'Enter the LKR amount.' });
            return;
        }

        const submitBtn = $('#save_expense_btn');
        const cancelBtn = $('#cancel_expense_edit_btn');

        if (isExpenseEditMode()) {
            const editExpenseId = $('#ex_edit_expense_id').val();

            submitBtn.prop('disabled', true).html('<i class="bi bi-hourglass"></i> Updating...');
            cancelBtn.prop('disabled', true);

            updateSavedExpense(editExpenseId, payload, invoiceImage).then(function (res) {
                if (res.previous_expense_date && res.previous_expense_date !== payload.expense_date) {
                    changeSavedExpenseCount(payload.tour_id, res.previous_expense_date, -1);
                    changeSavedExpenseCount(payload.tour_id, payload.expense_date, 1);
                }

                resetExpenseEntryFields(true);
                loadSavedExpenses(payload.tour_id, payload.expense_date);
                showSuccessToast(res.message || 'Expense updated.');
            }).catch(function (error) {
                Swal.fire({ icon: 'error', title: 'Error', text: error.message || 'Failed to update expense.' });
            }).finally(function () {
                submitBtn.prop('disabled', false);
                cancelBtn.prop('disabled', false);
                setExpenseFormMode(isExpenseEditMode() ? 'edit' : 'create');
            });

            return;
        }

        submitBtn.prop('disabled', true).html('<i class="bi bi-hourglass"></i> Saving...');

        uploadExpense(payload, invoiceImage).then(function () {
            changeSavedExpenseCount(payload.tour_id, payload.expense_date, 1);
            setPendingSavedExpenseCount(payload.tour_id, getPendingSavedExpenseCount(payload.tour_id) + 1);
            updateTourFinalizeButtons(payload.tour_id);
            resetExpenseEntryFields(true);
            loadSavedExpenses(payload.tour_id, payload.expense_date);
            showSuccessToast('Expense saved.');
            $('#ex_invoice_no').focus();
        }).catch(function (error) {
            Swal.fire({ icon: 'error', title: 'Error', text: error.message || 'Failed to save expense.' });
        }).finally(function () {
            submitBtn.prop('disabled', false);
        });
    });

    function jumpToToday(flash = true) {
        const todayCard = $('.dp-day-today').first();
        if (!todayCard.length) {
            return;
        }

        const tourBody = todayCard.closest('.dp-tour-body');
        if (tourBody.hasClass('collapsed')) {
            tourBody.closest('.dp-tour-card').find('[data-dp-toggle="tour-detail"]').trigger('click');
        }

        todayCard[0].scrollIntoView({ behavior: 'smooth', block: 'center' });

        if (flash) {
            todayCard.removeClass('dp-day-flash');
            requestAnimationFrame(function () {
                todayCard.addClass('dp-day-flash');
            });
        }
    }

    $('#jumpToTodayBtn').on('click', function () {
        jumpToToday(true);
    });

    if ($('.dp-day-today').length) {
        $('#jumpToTodayBtn').removeClass('d-none');
        setTimeout(function () {
            jumpToToday(true);
        }, 300);
    }

    buildExpenseAccountSelect();
    updateTourFinalizeButtons();
</script>
</body>
</html>
