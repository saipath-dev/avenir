<?= view('layouts/head') ?>
<style>
    .bg-purple {
        background-color: #6d28d9 !important;
        color: #fff !important;
    }
    .bg-orange {
        background-color: #ea580c !important;
        color: #fff !important;
    }
    .bg-teal {
        background-color: #0d9488 !important;
        color: #fff !important;
    }
    .tpd-stat-card {
        border-radius: 12px;
        border: none;
        box-shadow: 0 2px 8px rgba(0,0,0,.08);
    }
    .tpd-stat-icon {
        width: 52px; height: 52px;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.4rem;
    }
    .tpd-cat-card {
        border-radius: 10px;
        border: 1px solid #e9ecef;
        padding: 12px 16px;
        background: #fff;
    }
    .tpd-cat-badge {
        font-size: 0.7rem;
        padding: 3px 8px;
        border-radius: 20px;
    }
    .tpd-make-payment {
        position: sticky;
        top: 80px;
        max-height: calc(100vh - 100px);
        overflow-y: auto;
    }
    .tpd-progress-wrap { background:#f1f5f9; border-radius: 8px; height: 6px; }
    .tpd-progress-bar  { border-radius: 8px; height: 6px; }
    .tour-header-card  { border-radius: 14px; border: none; box-shadow: 0 2px 12px rgba(0,0,0,.10); }
    .category-icon-circle {
        width: 38px; height: 38px;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.1rem;
    }
    .color-primary  { background:#dbeafe; color:#1d4ed8; }
    .color-success  { background:#d1fae5; color:#065f46; }
    .color-info     { background:#cffafe; color:#0e7490; }
    .color-purple   { background:#ede9fe; color:#6d28d9; }
    .color-warning  { background:#fef3c7; color:#b45309; }
    #no-tour-msg { display:none; }
    #tour-content { display:none; }
    #upcoming-tours-panel { display:block; }

    #today_rate_banner {
        background: linear-gradient(90deg, #fef3c7, #fde68a);
        border: 1px solid #f59e0b;
        color: #92400e;
        border-radius: 10px;
    }
    #today_rate_banner.rate-missing {
        background: linear-gradient(90deg, #fee2e2, #fecaca);
        border-color: #ef4444;
        color: #991b1b;
    }

    .tpd-status-square {
        display:inline-block;
        width:18px; height:18px;
        border-radius:4px;
        margin:1px;
        cursor:default;
    }
    .tpd-status-squares {
        display:flex; flex-wrap:wrap; gap:3px; justify-content:center;
    }
    .tpd-legend {
        display:flex; flex-wrap:wrap; gap:10px 16px;
        font-size:.74rem; color:#475569;
    }
    .tpd-legend-top {
        margin-bottom:14px; padding-bottom:12px; border-bottom:1px solid #e9ecef;
    }
    .tpd-legend-item { display:flex; align-items:center; gap:5px; }
    .tpd-upcoming-row {
        cursor:pointer;
        transition: background-color .12s ease;
    }
    .tpd-upcoming-row:hover { background:#f8fafc; }

    .tpd-main-col {
        flex: 1 1 0;
        min-width: 0;
        transition: flex 0.25s ease;
    }
    .tpd-panel-col {
        flex: 0 0 33.33%;
        max-width: 33.33%;
        transition: flex 0.25s ease, max-width 0.25s ease;
    }
    #makePaymentToggleIcon {
        transition: transform 0.25s ease;
    }
    .tpd-panel-col.tpd-collapsed {
        flex: 0 0 56px;
        max-width: 56px;
    }
    .tpd-panel-col.tpd-collapsed #makePaymentBody,
    .tpd-panel-col.tpd-collapsed #makePaymentTitle,
    .tpd-panel-col.tpd-collapsed #makePaymentClearBtn {
        display: none;
    }
    .tpd-panel-col.tpd-collapsed .card-header {
        justify-content: center;
    }
    .tpd-panel-col.tpd-collapsed #makePaymentToggleIcon {
        transform: rotate(180deg);
    }
    @media (max-width: 991.98px) {
        .tpd-panel-col {
            flex: 1 1 100%;
            max-width: 100%;
        }
        .tpd-panel-col.tpd-collapsed {
            flex: 0 0 auto;
            max-width: 100%;
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

                <!-- Page Title + Search Bar -->
                <div class="row align-items-center mb-3">
                    <div class="col-md-5">
                        <h4 class="mb-0 text-primary fw-bold">Tour Payment Dashboard</h4>
                    </div>
                    <div class="col-md-7">
                        <div class="d-flex gap-2">
                            <input type="text" id="manual_id_input" class="form-control"
                                   placeholder="Enter Manual ID (e.g. TR-2026-0145)"
                                   value=""
                                   style="max-width:320px;">
                            <button class="btn btn-primary px-4" id="search_btn" onclick="searchTour()">
                                <i class="ri-search-line me-1"></i> Search
                            </button>
                        </div>
                    </div>
                </div>

                <!-- tf-offers-r3: hotel payments waiting for the System Administrator's rate review (holders only) -->
                <div id="rate_review_queue" class="card border-0 shadow-sm mb-3" style="display:none;"></div>

                <!-- Upcoming Tours Landing List (visible by default, before any search) -->
                <div id="upcoming-tours-panel">
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-body p-3">
                            <div class="d-flex flex-wrap align-items-end justify-content-between gap-2 mb-3">
                                <div>
                                    <h6 class="mb-1 fw-bold">Upcoming Tours</h6>
                                    <div class="text-muted small">Tours starting in the selected date range. Click a row to view full payment details.</div>
                                </div>
                                <div class="d-flex align-items-end gap-2">
                                    <div>
                                        <label class="form-label small mb-1">From</label>
                                        <input type="date" id="upcoming_from" class="form-control form-control-sm">
                                    </div>
                                    <div>
                                        <label class="form-label small mb-1">To</label>
                                        <input type="date" id="upcoming_to" class="form-control form-control-sm">
                                    </div>
                                    <button class="btn btn-sm btn-outline-primary" id="upcoming_refresh_btn" onclick="loadUpcomingTours()">
                                        <i class="ri-refresh-line"></i> Refresh
                                    </button>
                                </div>
                            </div>
                            <div class="tpd-legend tpd-legend-top">
                                <div class="tpd-legend-item"><span class="tpd-status-square" style="background:#22c55e;"></span>Fully Paid</div>
                                <div class="tpd-legend-item"><span class="tpd-status-square" style="background:#f59e0b;"></span>Pending Payment</div>
                                <!-- <div class="tpd-legend-item"><span class="tpd-status-square" style="background:#8b5cf6;"></span>Pending Approval</div> -->
                                <div class="tpd-legend-item"><span class="tpd-status-square" style="background:#3b82f6;"></span>Advance Paid</div>
                                <div class="tpd-legend-item"><span class="tpd-status-square" style="background:#14b8a6;"></span>Payment on Process</div>
                                <div class="tpd-legend-item"><span class="tpd-status-square" style="background:#92400e;"></span>Waiting Rate Confirmation</div>
                                <div class="tpd-legend-item"><span class="tpd-status-square" style="background:#f97316;"></span>Not Paid</div>
                                <div class="tpd-legend-item"><span class="tpd-status-square" style="background:#ef4444;"></span>Payment Not Requested</div>

                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0 align-middle" id="upcoming_tours_table">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Manual ID</th>
                                            <th>Consignee</th>
                                            <th>Start Date</th>
                                            <th>End Date</th>
                                            <th class="text-center">Pax</th>
                                            <th>Flights</th>
                                            <th class="text-center">Hotels</th>
                                            <th class="text-center">Activities</th>
                                            <th class="text-center">Driver Advance</th>
                                        </tr>
                                    </thead>
                                    <tbody id="upcoming_tours_body">
                                        <!-- Populated by JS -->
                                    </tbody>
                                </table>
                            </div>
                            <div id="upcoming_empty_msg" class="text-center text-muted py-4" style="display:none;">
                                <i class="ri-calendar-line fs-3 d-block mb-1"></i>
                                No tours starting in the selected date range.
                            </div>
                            <div id="upcoming_loading_msg" class="text-center text-muted py-4">
                                <i class="ri-loader-4-line fs-3 d-block mb-1"></i>
                                Loading upcoming tours...
                            </div>
                        </div>
                    </div>
                </div>

                <!-- No Tour Message -->
                <div id="no-tour-msg" class="alert alert-warning">
                    <i class="ri-information-line me-1"></i> No tour found for the entered Manual ID.
                </div>

                <!-- Tour Content (hidden until loaded) -->
                <div id="tour-content">
                    <div class="d-flex flex-column flex-lg-row gap-3" id="tourContentRow">

                        <!-- ==================== LEFT MAIN CONTENT ==================== -->
                        <div id="tourMainCol" class="tpd-main-col">

                            <!-- Back to Summary -->
                            <div class="mb-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="backToUpcomingSummary()">
                                    <i class="ri-arrow-left-line me-1"></i> Back to Summary
                                </button>
                            </div>

                            <!-- Tour Header Card -->
                            <div class="card tour-header-card mb-3">
                                <div class="card-body p-3">
                                    <div class="d-flex gap-3 align-items-start">
                                        <div style="width:90px;height:72px;border-radius:10px;overflow:hidden;flex-shrink:0;">
                                            <img src="<?= base_url('assets/images/tour_placeholder.jpg') ?>"
                                                 style="width:100%;height:100%;object-fit:cover;"
                                                 
                                                 alt="Tour" id="tph_img">
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                <h5 class="mb-0 fw-bold" id="tph_name">-</h5>
                                                <span class="badge" id="tph_status_badge">-</span>
                                            </div>
                                            <div class="d-flex flex-wrap gap-4 small text-muted">
                                                <div>
                                                    <div class="text-secondary" style="font-size:.7rem;">Tour ID</div>
                                                    <div class="fw-semibold text-dark" id="tph_manual_id">-</div>
                                                </div>
                                                <div>
                                                    <div class="text-secondary" style="font-size:.7rem;">Pax Count</div>
                                                    <div class="fw-semibold text-dark" id="tph_pax">-</div>
                                                </div>
                                                <div>
                                                    <div class="text-secondary" style="font-size:.7rem;">Travel Dates</div>
                                                    <div class="fw-semibold text-dark" id="tph_dates">-</div>
                                                </div>
                                                <div>
                                                    <div class="text-secondary" style="font-size:.7rem;">Agency</div>
                                                    <div class="fw-semibold text-dark" id="tph_agency">-</div>
                                                </div>
                                                <div>
                                                    <div class="text-secondary" style="font-size:.7rem;">Currency</div>
                                                    <div class="fw-semibold text-dark" id="tph_currency">LKR</div>
                                                </div>
                                                <div>
                                                    <div class="text-secondary" style="font-size:.7rem;">Allocated Drivers</div>
                                                    <div class="fw-semibold text-dark" id="tph_drivers">-</div>
                                                </div>
                                                <div>
                                                    <div class="text-secondary" style="font-size:.7rem;">Created By</div>
                                                    <div class="fw-semibold text-dark" id="tph_created_by">-</div>
                                                </div>
                                                <div>
                                                    <div class="text-secondary" style="font-size:.7rem;">Created Date</div>
                                                    <div class="fw-semibold text-dark" id="tph_created_date">-</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Summary Stat Cards -->
                            <div class="row g-2 mb-3" id="summary_cards">
                                <div class="col-sm-6 col-xl-3">
                                    <div class="card tpd-stat-card h-100">
                                        <div class="card-body d-flex align-items-center gap-3 py-3">
                                            <div class="tpd-stat-icon" style="background:#dbeafe;color:#1d4ed8;">
                                                <i class="ri-money-dollar-circle-line"></i>
                                            </div>
                                            <div>
                                                <div class="text-muted" style="font-size:.72rem;">Total Estimated Cost</div>
                                                <div class="fw-bold fs-6" id="stat_estimated">LKR 0</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-xl-3">
                                    <div class="card tpd-stat-card h-100">
                                        <div class="card-body d-flex align-items-center gap-3 py-3">
                                            <div class="tpd-stat-icon" style="background:#d1fae5;color:#065f46;">
                                                <i class="ri-checkbox-circle-line"></i>
                                            </div>
                                            <div>
                                                <div class="text-muted" style="font-size:.72rem;">Total Paid</div>
                                                <div class="fw-bold fs-6" id="stat_paid">LKR 0</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-xl-3">
                                    <div class="card tpd-stat-card h-100">
                                        <div class="card-body d-flex align-items-center gap-3 py-3">
                                            <div class="tpd-stat-icon" style="background:#fee2e2;color:#991b1b;">
                                                <i class="ri-file-list-3-line"></i>
                                            </div>
                                            <div>
                                                <div class="text-muted" style="font-size:.72rem;">Outstanding Balance</div>
                                                <div class="fw-bold fs-6 text-danger" id="stat_outstanding">LKR 0</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-xl-3">
                                    <div class="card tpd-stat-card h-100">
                                        <div class="card-body d-flex align-items-center gap-3 py-3">
                                            <div class="tpd-stat-icon" style="background:#ede9fe;color:#6d28d9;">
                                                <i class="ri-calendar-check-line"></i>
                                            </div>
                                            <div>
                                                <div class="text-muted" style="font-size:.72rem;">Upcoming Due This Week</div>
                                                <div class="fw-bold fs-6 text-warning" id="stat_upcoming">LKR 0</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Category Breakdown -->
                            <div class="d-flex gap-2 flex-wrap mb-3" id="category_cards">
                                <!-- Populated by JS -->
                            </div>

                            <!-- Today's Date / USD Rate Highlight -->
                            <div class="alert d-flex align-items-center gap-2 mb-3 py-2 px-3" id="today_rate_banner">
                                <i class="ri-calendar-event-line fs-5"></i>
                                <span class="fw-semibold" id="today_rate_date">-</span>
                                <span class="mx-1">|</span>
                                <i class="ri-exchange-dollar-line fs-5"></i>
                                <span class="fw-semibold" id="today_rate_text">Loading USD rate...</span>
                                <a href="<?= base_url('master_files/daily_usd_rate') ?>" target="_blank"
                                   class="ms-auto small text-decoration-none">
                                    <i class="ri-edit-line me-1"></i>Edit Rate
                                </a>
                            </div>

                            <!-- Payment Details Table -->
                            <div class="card border-0 shadow-sm mb-3">
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0" id="payment_table">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Day</th>
                                                    <th>Category</th>
                                                    <th>Supplier</th>
                                                    <th>Description</th>
                                                    <th class="text-end">Total Amount</th>
                                                    <th class="text-end">Advance Paid</th>
                                                    <th class="text-end">Balance</th>
                                                    <th>Request Date</th>
                                                    <th>Due Date</th>
                                                    <th>Status</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody id="payment_table_body">
                                                <!-- Populated by JS -->
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="px-3 py-2 text-muted small d-flex justify-content-between align-items-center">
                                        <span id="table_showing">Showing 0 entries</span>
                                        <span id="tpd_cost_tools" style="display:none;">
                                            <span class="me-2">Hotel costs from the Hotel Rates panel: <span class="badge bg-success">Ad hoc</span> <span class="badge bg-info text-dark">Contracted</span> <span class="badge bg-danger">Quoted — check</span></span>
                                            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" onclick="recalcHotelCosts()"><i class="ri-refresh-line"></i> Recalculate</button>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Tickets (Usage Notes) -->
                            <div class="card border-0 shadow-sm mb-3">
                                <div class="card-body p-0">
                                    <div class="px-3 pt-3 pb-2 d-flex justify-content-between align-items-center">
                                        <h6 class="fw-semibold mb-0"><i class="ri-ticket-2-line me-1"></i>Tickets</h6>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0" id="tickets_table">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Usage Note No</th>
                                                    <th>Date</th>
                                                    <th>Items</th>
                                                    <th class="text-center">Qty</th>
                                                    <th>Status</th>
                                                    <th>Approved By</th>
                                                </tr>
                                            </thead>
                                            <tbody id="tickets_table_body">
                                                <!-- Populated by JS -->
                                            </tbody>
                                        </table>
                                    </div>
                                    <div id="tickets_empty_msg" class="text-center text-muted py-4" style="display:none;">
                                        <i class="ri-ticket-2-line fs-3 d-block mb-1"></i>
                                        No tickets requested for this tour.
                                    </div>
                                </div>
                            </div>

                            <!-- Payment Progress by Category -->
                            <div class="card border-0 shadow-sm">
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-8">
                                            <h6 class="fw-semibold mb-3">Payment Progress by Category</h6>
                                            <div class="row g-3" id="progress_by_category">
                                                <!-- Populated by JS -->
                                            </div>
                                        </div>
                                        <div class="col-md-4 text-center">
                                            <h6 class="fw-semibold mb-3">Overall Payment Progress</h6>
                                            <div style="position:relative;display:inline-block;">
                                                <div id="overallProgressChart" style="width:140px;height:140px;"></div>
                                                <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);font-size:1.1rem;font-weight:700;pointer-events:none;" id="overall_pct">0%</div>
                                            </div>
                                            <div class="mt-3 small">
                                                <div class="d-flex justify-content-between"><span>Paid</span><span class="fw-semibold text-success" id="prog_paid_lbl">0</span></div>
                                                <div class="d-flex justify-content-between"><span>Remaining</span><span class="fw-semibold text-danger" id="prog_remain_lbl">0</span></div>
                                                <div class="d-flex justify-content-between"><span>Total</span><span class="fw-semibold" id="prog_total_lbl">0</span></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div><!-- /tourMainCol -->

                        <!-- ==================== RIGHT: MAKE PAYMENT PANEL ==================== -->
                        <!-- Hidden for now — not needed this release, keep markup/JS intact for future use -->
                        <div id="makePaymentCol" class="tpd-panel-col" style="display:none;">
                            <div class="card border-0 shadow tpd-make-payment h-100" id="makePaymentCard">
                                <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center py-3">
                                    <h6 class="mb-0 fw-bold" id="makePaymentTitle">Make Payment</h6>
                                    <div class="d-flex align-items-center gap-1">
                                        <button class="btn btn-sm btn-light" id="makePaymentClearBtn" onclick="clearPaymentForm()" title="Clear">
                                            <i class="ri-refresh-line"></i>
                                        </button>
                                        <button class="btn btn-sm btn-light" type="button" onclick="toggleMakePaymentPanel()" title="Collapse panel">
                                            <i class="ri-arrow-right-s-line" id="makePaymentToggleIcon"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body px-3 py-3" id="makePaymentBody">

                                    <!-- Balance Summary -->
                                    <div class="bg-light rounded p-3 mb-3">
                                        <div class="fw-semibold text-muted small mb-2">Balance Summary</div>
                                        <div class="d-flex justify-content-between small">
                                            <span>Total Amount</span>
                                            <span class="fw-semibold" id="pay_total_amt">-</span>
                                        </div>
                                        <div class="d-flex justify-content-between small">
                                            <span>Paid So Far</span>
                                            <span class="fw-semibold text-success" id="pay_paid_so_far">-</span>
                                        </div>
                                        <hr class="my-1">
                                        <div class="d-flex justify-content-between">
                                            <span class="fw-semibold">Remaining Balance</span>
                                            <span class="fw-bold text-danger" id="pay_remaining">-</span>
                                        </div>
                                    </div>

                                    <!-- Hidden fields -->
                                    <input type="hidden" id="pay_tour_id">
                                    <input type="hidden" id="pay_manual_id">
                                    <input type="hidden" id="pay_supplier_acc">
                                    <input type="hidden" id="pay_row_total">

                                    <!-- Supplier -->
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold small">Supplier <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control form-control-sm" id="pay_supplier_name" placeholder="Select a row to fill" readonly>
                                    </div>

                                    <!-- Payment Category -->
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold small">Payment Category <span class="text-danger">*</span></label>
                                        <select class="form-select form-select-sm" id="pay_category">
                                            <option value="">-- Select Category --</option>
                                            <option value="Hotel">Hotel</option>
                                            <option value="Activity">Activity</option>
                                            <option value="Transport">Transport</option>
                                            <option value="Driver Advance">Driver Advance</option>
                                            <option value="Driver">Driver Daily Bata</option>
                                            <option value="Other">Other</option>
                                        </select>
                                    </div>

                                    <!-- Amount -->
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold small">Amount to Pay <span class="text-danger">*</span></label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text">LKR</span>
                                            <input type="number" class="form-control" id="pay_amount" placeholder="0.00" step="0.01" min="0">
                                        </div>
                                    </div>

                                    <!-- Payment Method -->
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold small">Payment Method <span class="text-danger">*</span></label>
                                        <select class="form-select form-select-sm" id="pay_method">
                                            <option value="">Select Payment Method</option>
                                            <option value="Bank Transfer">Bank Transfer</option>
                                            <option value="Cheque">Cheque</option>
                                            <option value="Cash">Cash</option>
                                            <option value="Online">Online Transfer</option>
                                        </select>
                                    </div>

                                    <!-- Bank Account -->
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold small">Pay From Bank <span class="text-danger">*</span></label>
                                        <select class="form-select form-select-sm" id="pay_bank_acc">
                                            <option value="">-- Select Bank Account --</option>
                                        </select>
                                    </div>

                                    <!-- Payment Date -->
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold small">Payment Date <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control form-control-sm" id="pay_date"
                                               value="<?= date('Y-m-d') ?>">
                                    </div>

                                    <!-- Reference No -->
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold small">Reference No.</label>
                                        <input type="text" class="form-control form-control-sm" id="pay_reference" placeholder="Enter reference number">
                                    </div>

                                    <!-- Remarks -->
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold small">Remarks</label>
                                        <input type="text" class="form-control form-control-sm" id="pay_remarks" placeholder="Enter remarks (optional)">
                                    </div>

                                    <!-- Upload Receipt -->
                                    <div class="mb-3">
                                        <div class="border rounded p-3 text-center text-muted" style="border-style:dashed!important;cursor:pointer;">
                                            <i class="ri-upload-cloud-line fs-4"></i>
                                            <div class="small mt-1">Upload Receipt</div>
                                            <div style="font-size:.68rem;">PNG, JPG, PDF up to 5MB</div>
                                        </div>
                                    </div>

                                    <!-- Buttons -->
                                    <div class="d-grid gap-2">
                                        <!-- <button class="btn btn-primary" onclick="submitPayment(false, false)">
                                            <i class="ri-save-line me-1"></i> Save Payment
                                        </button>
                                        <button class="btn btn-outline-secondary" onclick="submitPayment(true, false)">
                                            Save as Advance
                                        </button> -->
                                        <button class="btn btn-outline-success" onclick="submitPayment(false, true)">
                                            Mark as Fully Paid
                                        </button>
                                    </div>

                                </div>
                            </div>
                        </div><!-- /makePaymentCol -->

                    </div><!-- /tourContentRow -->
                </div><!-- /tour-content -->

            </div>
        </div>
    </div>

</div><!-- /layout-wrapper -->

<!-- ==================== PAYMENT REQUEST MODAL ==================== -->
<div class="modal fade" id="paymentRequestModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Payment Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="bg-light rounded p-3 mb-3 small">
                    <div class="d-flex justify-content-between"><span>Supplier</span><span class="fw-semibold" id="pr_supplier_name">-</span></div>
                    <div class="d-flex justify-content-between"><span>Category</span><span class="fw-semibold" id="pr_category_lbl">-</span></div>
                    <div class="d-flex justify-content-between"><span>Total Amount</span><span class="fw-semibold" id="pr_total_amt">-</span></div>
                    <!-- Hotel cost handoff: what the Hotel Rates panel says we pay (ad hoc → contract → quoted) -->
                    <div class="d-flex justify-content-between" id="pr_cost_row" style="display:none;">
                        <span>Hotel cost (payable)</span><span><span class="fw-semibold" id="pr_cost_amt">-</span> <span class="badge" id="pr_cost_badge"></span></span>
                    </div>
                    <div class="text-muted" id="pr_cost_detail" style="display:none; font-size:.75rem;"></div>
                    <!-- tf-offers-r3: a hotel payment held for the rate review — who prepared it, when, for how much -->
                    <div id="pr_release_info" class="mt-2 border rounded p-2 bg-white" style="display:none; font-size:.8rem;"></div>
                    <!-- tf-crew-master-r1: a driver's or guide's payment — who is paid, from the Driver & Guide Master -->
                    <div id="pr_payee_box" class="mt-2 border rounded p-2 bg-white" style="display:none; font-size:.8rem;"></div>
                    <!-- tf-offers-r1: published rate · tactical / early bird · rate used for the hotel payment · saving -->
                    <div id="pr_offer_box" class="mt-2" style="display:none;"></div>
                    <div class="d-flex justify-content-between align-items-center" id="pr_rate_row" style="display:none;">
                        <span>Today's USD Rate</span>
                        <span>
                            <span class="fw-semibold" id="pr_usd_rate">-</span>
                            <a href="<?= base_url('master_files/daily_usd_rate') ?>" target="_blank"
                               class="ms-1 text-decoration-none" title="Edit USD rate">
                                <i class="ri-edit-line"></i>
                            </a>
                        </span>
                    </div>
                    <div class="d-flex justify-content-between" id="pr_lkr_row" style="display:none;">
                        <span>Est. LKR Amount</span><span class="fw-semibold text-primary" id="pr_lkr_amt">-</span>
                    </div>
                </div>
                <div class="alert alert-warning small py-2 px-3 mb-3" id="pr_rate_missing_alert" style="display:none;">
                    <i class="ri-error-warning-line me-1"></i> Today's USD rate hasn't been entered yet.
                    <a href="<?= base_url('master_files/daily_usd_rate') ?>" target="_blank" class="alert-link">Set it now</a>.
                </div>
                <input type="hidden" id="pr_row_idx">
                <div class="mb-3">
                    <label class="form-label fw-semibold small" id="pr_amount_label">Request Amount <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="pr_amount" placeholder="Enter amount">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-outline-secondary" id="pr_return_btn" style="display:none;" onclick="returnHeldPayment()">
                    <i class="ri-arrow-go-back-line me-1"></i> Return to staff
                </button>
                <button type="button" class="btn btn-warning" id="pr_submit_btn" onclick="submitPaymentRequest()">
                    <i class="ri-send-plane-line me-1"></i> Send Request
                </button>
            </div>
        </div>
    </div>
</div>

    <?= view('layouts/inpage_footer') ?>
    <?= view('layouts/theme_settings') ?>
    <?= view('layouts/scripts') ?>
<script>
// ==========================================
//  State
// ==========================================
let _tourData = null;
const voucherPrintBaseUrl = '<?= base_url('accounts/voucher_print/') ?>';
// tf-offers-r1: Published rate = quotation basis and the payment by default; a Tactical / Early Bird that fits the stay is
// flagged here to a sign-in holding "Tactical / Early Bird rate selection" (only they get row.cost_offer), who applies it
// or keeps the published rate
const CAN_CHOOSE_OFFER = <?= !empty($can_choose_offer) ? 'true' : 'false' ?>;
const OFFER_CHOOSE_URL = '<?= base_url('master_files/offer_selection/choose') ?>';
// tf-offers-r3: the review before a hotel payment is logged (who, when); the terms of a special rate, for holders only
const OFFER_REVIEWED_URL = '<?= base_url('master_files/offer_selection/reviewed') ?>';
const OFFER_TERMS_URL = '<?= base_url('master_files/offer_selection/offer') ?>/';
// tf-offers-r3: hotel payments held for the rate review — the list, release to Payment approval, return to staff
const OFFER_HOLDS_URL = '<?= base_url('master_files/offer_selection/holds') ?>';
const OFFER_RELEASE_URL = '<?= base_url('master_files/offer_selection/release') ?>';
const OFFER_RETURN_URL = '<?= base_url('master_files/offer_selection/return') ?>';
const OFFER_KIND = { tactical: 'Tactical rate', early_bird: 'Early Bird' };

// ==========================================
//  Upcoming Tours Landing List
// ==========================================
function _fmtDateInput(d) {
    // yields YYYY-MM-DD for <input type="date">
    return d.toISOString().slice(0, 10);
}

function initUpcomingDateDefaults() {
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    const twoWeeks = new Date();
    twoWeeks.setDate(twoWeeks.getDate() + 14);
    document.getElementById('upcoming_from').value = _fmtDateInput(tomorrow);
    document.getElementById('upcoming_to').value   = _fmtDateInput(twoWeeks);
}

function loadUpcomingTours() {
    const from = document.getElementById('upcoming_from').value;
    const to   = document.getElementById('upcoming_to').value;

    document.getElementById('upcoming_loading_msg').style.display = 'block';
    document.getElementById('upcoming_empty_msg').style.display   = 'none';
    document.getElementById('upcoming_tours_body').innerHTML = '';

    const params = new URLSearchParams();
    if (from) params.set('from', from);
    if (to)   params.set('to', to);

    fetch(`<?= base_url('tour/tour_payment_dashboard/list_upcoming') ?>?${params.toString()}`)
        .then(r => r.json())
        .then(res => {
            document.getElementById('upcoming_loading_msg').style.display = 'none';
            if (res.status === 200) {
                renderUpcomingTours(res.data || []);
            } else {
                renderUpcomingTours([]);
            }
        })
        .catch(() => {
            document.getElementById('upcoming_loading_msg').style.display = 'none';
            renderUpcomingTours([]);
        });
}

// One small square per row in this category — e.g. 4 hotels renders 4 squares,
// each colored via the SAME statusClass() mapping the detail view's Status
// column already uses, so a square's color always means the same thing here
// as it does after clicking into the full detail. A hyphen shows for an empty
// category (no rows of that type on this tour) rather than a blank cell.
// Dedicated palette for the upcoming-tours squares — deliberately separate from
// statusClass() (used by the single-tour detail view's Status badges below).
// Red is reserved for the most severe case (nobody's even requested payment
// yet); Not Paid (a request exists, nothing's moved) is one step down at
// orange. Waiting Rate Confirmation gets its own color rather than sharing,
// since it's a genuinely different kind of blocker (needs a daily USD rate
// set before the request can even be made) from the others it might
// otherwise be lumped with.
const UPCOMING_STATUS_COLORS = {
    'Fully Paid':                '#22c55e',
    'Paid':                      '#22c55e',
    'Partially Paid':            '#f59e0b',
    'Pending Payment':           '#f59e0b',
    'Pending Approval':          '#8b5cf6',
    'Requested':                 '#8b5cf6',
    'Advance Paid':              '#3b82f6',
    'Payment on Process':        '#14b8a6',
    'Waiting Rate Confirmation': '#92400e',
    'Not Paid':                  '#f97316',
    'Payment Not Requested':     '#ef4444',
    'Cancelled':                 '#6b7280',
};

function _statusSquaresHtml(statuses) {
    if (!statuses || !statuses.length) {
        return '<span class="text-muted small">-</span>';
    }
    const squares = statuses.map(s => {
        const color = UPCOMING_STATUS_COLORS[s] || '#6b7280';
        return `<span class="tpd-status-square" style="background:${color};" title="${esc(s)}"></span>`;
    }).join('');
    return `<div class="tpd-status-squares">${squares}</div>`;
}

// Shows arrival/departure flight + time when set on the tour, matching the
// "CODE - TIME" convention already used elsewhere (reservation_card_print.php).
// Falls back to a dash when neither is available.
function _flightsHtml(t) {
    const lines = [];
    if (t.arrival_flight) {
        lines.push(`<div><i class="ri-flight-land-line text-muted me-1"></i>${esc(t.arrival_flight)}${t.arrival_flight_time ? ' - ' + esc(t.arrival_flight_time) : ''}</div>`);
    }
    if (t.departure_flight) {
        lines.push(`<div><i class="ri-flight-takeoff-line text-muted me-1"></i>${esc(t.departure_flight)}${t.departure_flight_time ? ' - ' + esc(t.departure_flight_time) : ''}</div>`);
    }
    return lines.length ? lines.join('') : '<span class="text-muted small">-</span>';
}

function renderUpcomingTours(tours) {
    const tbody = document.getElementById('upcoming_tours_body');
    tbody.innerHTML = '';

    if (!tours.length) {
        document.getElementById('upcoming_empty_msg').style.display = 'block';
        return;
    }
    document.getElementById('upcoming_empty_msg').style.display = 'none';

    tours.forEach(t => {
        const cats = t.categories || {};
        const tr = document.createElement('tr');
        tr.className = 'tpd-upcoming-row';
        tr.innerHTML = `
            <td class="fw-semibold text-primary">${esc(t.manual_id)}</td>
            <td>${esc(t.consignee)}</td>
            <td>${fmtDate(t.start_date)}</td>
            <td>${fmtDate(t.end_date)}</td>
            <td class="text-center">${t.passenger_count ?? '-'}</td>
            <td class="small">${_flightsHtml(t)}</td>
            <td class="text-center">${_statusSquaresHtml(cats.hotels)}</td>
            <td class="text-center">${_statusSquaresHtml(cats.activities)}</td>
            <td class="text-center">${_statusSquaresHtml(cats.driver_advance)}</td>
        `;
        tr.addEventListener('click', () => selectUpcomingTour(t.manual_id));
        tbody.appendChild(tr);
    });
}

function selectUpcomingTour(manualId) {
    document.getElementById('manual_id_input').value = manualId;
    document.getElementById('upcoming-tours-panel').style.display = 'none';
    searchTour();
}

function backToUpcomingSummary() {
    document.getElementById('tour-content').style.display   = 'none';
    document.getElementById('no-tour-msg').style.display    = 'none';
    document.getElementById('manual_id_input').value = '';
    document.getElementById('upcoming-tours-panel').style.display = 'block';
    // Refresh — the user may have just made a payment on the tour they're
    // leaving, so the list should reflect its updated status, not a stale one.
    loadUpcomingTours();
}

document.addEventListener('DOMContentLoaded', function () {
    initUpcomingDateDefaults();
    loadUpcomingTours();
    loadRateReviewQueue();   // tf-offers-r3
    // deep link from Hotel Incomplete Work / other pages: ?manual_id=AH 1234 opens that tour
    const _mid = new URLSearchParams(window.location.search).get('manual_id');
    if (_mid) { document.getElementById('manual_id_input').value = _mid; searchTour(); }
});
document.getElementById('upcoming_from').addEventListener('change', loadUpcomingTours);
document.getElementById('upcoming_to').addEventListener('change', loadUpcomingTours);

// ==========================================
//  Search Tour (AJAX)
// ==========================================
function searchTour() {
    const manualId = document.getElementById('manual_id_input').value.trim();
    if (!manualId) {
        Swal.fire('Warning', 'Please enter a Manual ID', 'warning');
        return;
    }

    document.getElementById('tour-content').style.display  = 'none';
    document.getElementById('no-tour-msg').style.display   = 'none';

    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 4000);

    fetch(`<?= base_url('tour/tour_payment_dashboard/search_tour') ?>?manual_id=${encodeURIComponent(manualId)}`, {
            signal: controller.signal
        })
        .then(r => r.json())
        .then(res => {
            clearTimeout(timeoutId);
            if (res.status === 200) {
                _tourData = res.data;
                // Needed here (not just in the landing-list click handler) so a
                // user who ignores the landing list and manually searches doesn't
                // end up with both panels visible at once.
                document.getElementById('upcoming-tours-panel').style.display = 'none';
                // show container FIRST so ApexCharts can measure the element
                document.getElementById('tour-content').style.display = 'block';
                renderDashboard(res.data);
                loadBankAccounts();
                loadUsdRate();
            } else {
                document.getElementById('no-tour-msg').style.display = 'block';
                // Re-show the landing list so a failed search triggered from a
                // landing-list click doesn't strand the user with no way back.
                document.getElementById('upcoming-tours-panel').style.display = 'block';
            }
        })
        .catch((err) => {
            clearTimeout(timeoutId);
            if (err.name === 'AbortError') {
                Swal.fire('Timeout', 'Request took too long and was stopped. Please try again.', 'error');
            } else {
                Swal.fire('Error', 'Failed to load tour data.', 'error');
            }
        });
}

// Allow Enter key on search input
document.getElementById('manual_id_input').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') searchTour();
});

// Today's USD rate — used to preview the LKR amount a Hotel payment request
// will post at (same rate payment_request() applies server-side). Shown on the
// highlighted date/rate banner above the payment table, and inside the
// Payment Request modal (Hotel category only).
//
// Re-fetched (not cached forever) so an insert/edit/delete made on the Daily
// USD Rate page — in another tab, or before returning here — is picked up
// without a full page reload: on tab focus/visibility regain, and always
// right before the Payment Request modal opens.
let _todayUsdRate = null;
let _usdRateLoaded = false;
function loadUsdRate(force) {
    if (_usdRateLoaded && !force) return;
    fetch('<?= base_url('tour/tour_payment_dashboard/get_usd_rate') ?>', {
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
    })
        .then(r => r.json())
        .then(res => {
            if (res.status === 200) {
                _todayUsdRate = res.rate ? parseFloat(res.rate) : null;
            }
            _usdRateLoaded = true;
            updateTodayRateBanner();
            refreshOpenPaymentRequestModalRate();
        })
        .catch(() => { _usdRateLoaded = true; updateTodayRateBanner(); });
}

// If the Payment Request modal is currently open on a Hotel row, re-apply the
// just-refreshed rate to it too (covers: modal open + tab regains focus, or
// the forced fetch triggered by opening the modal resolving a beat later).
function refreshOpenPaymentRequestModalRate() {
    const modalEl = document.getElementById('paymentRequestModal');
    if (!modalEl.classList.contains('show')) return;
    const rowIdx = document.getElementById('pr_row_idx').value;
    if (rowIdx === '' || !_tourData) return;
    const row = _tourData.payment_rows[rowIdx];
    if (row.category === 'Hotel') refreshPaymentRequestRateUi(true);
}

// Picks up a rate change made on the Daily USD Rate page while this dashboard
// tab was in the background.
document.addEventListener('visibilitychange', function () {
    if (document.visibilityState === 'visible') loadUsdRate(true);
});
window.addEventListener('focus', function () { loadUsdRate(true); });

function updateTodayRateBanner() {
    const banner   = document.getElementById('today_rate_banner');
    const dateEl   = document.getElementById('today_rate_date');
    const rateEl   = document.getElementById('today_rate_text');
    if (!banner) return;

    dateEl.textContent = fmtDate(new Date().toISOString().slice(0, 10));

    if (_todayUsdRate) {
        rateEl.textContent = '1 $ = LKR ' + fmtRate(_todayUsdRate);
        banner.classList.remove('rate-missing');
    } else if (_usdRateLoaded) {
        rateEl.textContent = "USD rate not set for today";
        banner.classList.add('rate-missing');
    } else {
        rateEl.textContent = 'Loading USD rate...';
    }
}
document.addEventListener('DOMContentLoaded', function () {
    updateTodayRateBanner();
    loadUsdRate();
});

// Load bank accounts only once after first search
let _banksLoaded = false;
function loadBankAccounts() {
    if (_banksLoaded) return;
    fetch('<?= base_url('tour/tour_payment_dashboard/get_bank_accounts') ?>', {
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
    })
        .then(r => r.json())
        .then(res => {
            if (res.status !== 200) return;
            const sel = document.getElementById('pay_bank_acc');
            res.data.forEach(b => {
                const opt = document.createElement('option');
                opt.value = b.L_Code;
                opt.dataset.name = b.PName;
                opt.textContent  = b.PName;
                sel.appendChild(opt);
            });
            _banksLoaded = true;
        });
}

// ==========================================
//  Render Dashboard
// ==========================================
function renderDashboard(data) {
    const t   = data.tour_info;
    const tot = data.totals;
    const cats = data.categories;
    const rows = data.payment_rows;

    // ---- Tour Header ----
    document.getElementById('tph_name').textContent      = t.consignee || '-';
    document.getElementById('tph_manual_id').textContent = t.manual_id || '-';
    document.getElementById('tph_pax').textContent       = t.passenger_count || '-';
    document.getElementById('tph_dates').textContent     = (t.start_date ? fmtDate(t.start_date) : '-') + ' – ' + (t.end_date ? fmtDate(t.end_date) : '-');
    document.getElementById('tph_agency').textContent    = t.agency_name || t.agent_name || '-';

    const drivers = data.allocated_drivers || [];
    document.getElementById('tph_drivers').textContent = drivers.length ? drivers.join(', ') : '-';

    document.getElementById('tph_created_by').textContent   = t.create_user || '-';
    // create_time is a DATETIME ("YYYY-MM-DD HH:MM:SS") — replace the space with "T" so
    // it parses as ISO 8601 consistently across browsers (Safari doesn't reliably accept
    // the space-separated MySQL format), then format with fmtDate like the other dates here.
    document.getElementById('tph_created_date').textContent = t.create_time ? fmtDate(t.create_time.replace(' ', 'T')) : '-';

    const statusBadge = document.getElementById('tph_status_badge');
    statusBadge.textContent = data.tour_status;
    statusBadge.className   = 'badge ' + statusColor(data.tour_status);

    // ---- Stat Cards ----
    document.getElementById('stat_estimated').textContent  = 'LKR ' + fmtNum(tot.estimated);
    document.getElementById('stat_paid').textContent       = 'LKR ' + fmtNum(tot.paid);
    document.getElementById('stat_outstanding').textContent= 'LKR ' + fmtNum(tot.outstanding);
    document.getElementById('stat_upcoming').textContent   = 'LKR ' + fmtNum(tot.upcoming_due);

    // ---- Category Cards ----
    const catContainer = document.getElementById('category_cards');
    catContainer.innerHTML = '';
    Object.entries(cats).forEach(([key, cat]) => {
        const pct = cat.total > 0 ? Math.round((cat.paid / cat.total) * 100) : 0;
        const badge = pct >= 100 ? 'Fully Paid' : (pct > 0 ? 'Partially Paid' : 'Not Paid');
        const badgeCls = pct >= 100 ? 'bg-success' : (pct > 0 ? 'bg-warning text-dark' : 'bg-danger');
        catContainer.innerHTML += `
        <div class="tpd-cat-card flex-fill" style="min-width:140px;">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <div class="d-flex align-items-center gap-2">
                    <div class="category-icon-circle color-${cat.color}"><i class="${cat.icon}"></i></div>
                    <span class="fw-semibold small">${cat.label}</span>
                </div>
                <span class="badge rounded-pill ${badgeCls}">${badge}</span>
            </div>
            <div class="d-flex justify-content-between small text-muted mt-2">
                <span>Total<br><strong>${fmtNum(cat.total)}</strong></span>
                <span>Paid<br><strong>${fmtNum(cat.paid)}</strong></span>
                <span>Balance<br><strong>${fmtNum(cat.balance)}</strong></span>
            </div>
        </div>`;
    });

    // ---- Payment Table ----
    const tbody = document.getElementById('payment_table_body');
    tbody.innerHTML = '';
    rows.forEach((row, idx) => {
        const statusCls = statusClass(row.status);
        const canPay     = row.status === 'Not Paid' || row.status === 'Partially Paid';
        // Driver Advance can only be requested once the driver has actually submitted
        // an advance request (driver_advance_request row) — nothing to push otherwise.
        const canRequest = row.status === 'Payment Not Requested'
            && (['Hotel', 'Activity'].includes(row.category)
                || (row.category === 'Driver Advance' && !!row.driver_advance_request_id));
        let btnHtml = '<span class="text-muted small">-</span>';
        // Pay Now hidden for now — not needed this release. Keep canPay/fillPaymentForm
        // intact for future use; just don't render the button.
        // if (canPay) {
        //     btnHtml = `<button class="btn btn-sm btn-outline-primary py-0 px-2" onclick="fillPaymentForm(${idx})">Pay Now</button>`;
        // } else
        if (canRequest) {
            btnHtml = `<button class="btn btn-sm btn-outline-warning py-0 px-2" onclick="openPaymentRequestModal(${idx})">Payment Request</button>`;
        }
        // tf-offers-r3: held for the System Administrator's rate review — nothing requested yet
        const rr = row.category === 'Hotel' ? row.rate_review : null;
        const held = !!(rr && rr.status === 'awaiting' && row.status === 'Payment Not Requested');
        if (held) {
            btnHtml = CAN_CHOOSE_OFFER && rr.hold_id
                ? `<button class="btn btn-sm btn-warning py-0 px-2 rate-review-btn" onclick="openRateReview(${idx})">Review &amp; release</button>`
                : `<span class="badge bg-orange rate-review-wait" title="Prepared by ${esc(rr.prepared_by)} on ${esc(String(rr.prepared_at || '').slice(0, 16))}. The System Administrator confirms the rate, then it goes to Payment approval.">Awaiting rate review</span>`;
        } else if (rr && rr.status === 'returned' && row.status === 'Payment Not Requested') {
            btnHtml += `<div class="small text-muted rate-review-returned" style="max-width:220px; white-space:normal;">Returned by ${esc(rr.returned_by || '')}${rr.note ? ': ' + esc(rr.note) : ''}</div>`;
        }
        const catColor = catColorClass(row.category);
        const descHtml = row.voucher_no
            ? `<a href="${voucherPrintBaseUrl}${row.voucher_no}?tourid=${t.tour_id}" target="_blank" class="text-dark text-decoration-none"><i class="ri-file-text-line text-secondary me-1"></i>${esc(row.description)}</a>`
            : `<i class="ri-file-text-line text-secondary me-1"></i>${esc(row.description)}`;
        // Not-yet-requested hotel rows: show the $ Q Amount (tour_accounts' own
        // display for this state) instead of an LKR total, since no rate has
        // been applied/locked in yet at this point.
        const isHotelNotRequested = row.category === 'Hotel' && row.status === 'Payment Not Requested';
        // LKR amount in bigger font; when a per-row USD rate was locked in at request
        // time (Hotel only — everything else is LKR-native), show the $ x rate below
        // it as subtext, matching what was actually converted for this request.
        let totalCellHtml;
        if (isHotelNotRequested) {
            totalCellHtml = `<span class="fw-semibold">$ ${fmtNum(row.total_dollar ?? 0)}</span>`;
        } else {
            totalCellHtml = `<div class="fw-semibold" style="font-size:1.05rem;">LKR ${fmtNum(row.total)}</div>`;
            if (row.usd_rate) {
                totalCellHtml += `<div class="text-muted" style="font-size:.75rem;">$ ${fmtNum(row.total_dollar ?? 0)} @ ${fmtRate(row.usd_rate)}</div>`;
            }
        }
        // Hotel cost handoff: payable amount per the Hotel Rates panel, with its source
        if (row.category === 'Hotel' && row.cost_dollar !== null && row.cost_dollar !== undefined) {
            totalCellHtml += _hotelCostHtml(row, idx);
        }
        tbody.innerHTML += `<tr>
            <td><span class="badge bg-light text-dark border">${esc(row.day)}</span></td>
            <td><span class="badge ${catColor}">${esc(row.category)}</span></td>
            <td class="small">${esc(row.supplier)}${_payeeLineHtml(row)}</td>
            <td class="small text-dark">${descHtml}</td>
            <td class="text-end">${totalCellHtml}</td>
            <td class="text-end small">${fmtNum(row.paid)}</td>
            <td class="text-end small fw-semibold">${fmtNum(row.balance)}</td>
            <td class="small">${row.request_date ? fmtDate(row.request_date) : '-'}</td>
            <td class="small">${_stayDatesHtml(row)}</td>
            <td><span class="badge rounded-pill ${held ? 'bg-orange' : statusCls}">${esc(held ? 'Awaiting rate review' : row.status)}</span></td>
            <td>${btnHtml}</td>
        </tr>`;
    });
    document.getElementById('table_showing').textContent = `Showing 1 to ${rows.length} of ${rows.length} entries`;
    document.getElementById('tpd_cost_tools').style.display = rows.some(r => r.category === 'Hotel') ? '' : 'none';

    // ---- Tickets ----
    renderTickets(data.tickets || []);

    // ---- Progress by Category ----
    const progContainer = document.getElementById('progress_by_category');
    progContainer.innerHTML = '';
    Object.entries(cats).forEach(([key, cat]) => {
        const pct = cat.total > 0 ? Math.round((cat.paid / cat.total) * 100) : 0;
        progContainer.innerHTML += `
        <div class="col-6">
            <div class="d-flex justify-content-between small mb-1">
                <div class="d-flex align-items-center gap-1">
                    <div class="category-icon-circle color-${cat.color}" style="width:24px;height:24px;font-size:.7rem;">
                        <i class="${cat.icon}"></i>
                    </div>
                    <span>${cat.label}</span>
                </div>
                <span class="fw-bold" style="color:var(--bs-${cat.color === 'purple' ? 'purple' : cat.color})">${pct}%</span>
            </div>
            <div class="small text-muted mb-1">Paid ${fmtNum(cat.paid)} of ${fmtNum(cat.total)}</div>
            <div class="tpd-progress-wrap">
                <div class="tpd-progress-bar bg-${cat.color === 'purple' ? 'purple' : cat.color}" style="width:${pct}%"></div>
            </div>
        </div>`;
    });

    // ---- Overall Doughnut Chart ----
    const overallPct = tot.estimated > 0 ? Math.round((tot.paid / tot.estimated) * 100) : 0;
    document.getElementById('overall_pct').textContent = overallPct + '%';
    document.getElementById('prog_paid_lbl').textContent    = fmtNum(tot.paid);
    document.getElementById('prog_remain_lbl').textContent  = fmtNum(tot.outstanding);
    document.getElementById('prog_total_lbl').textContent   = fmtNum(tot.estimated);

    if (window._overallChart) {
        window._overallChart.destroy();
    }
    window._overallChart = new ApexCharts(document.getElementById('overallProgressChart'), {
        series: [Math.max(0, tot.paid), Math.max(0, tot.outstanding)],
        chart: { type: 'donut', width: 140, height: 140, sparkline: { enabled: true } },
        labels: ['Paid', 'Remaining'],
        colors: ['#22c55e', '#e5e7eb'],
        legend: { show: false },
        dataLabels: { enabled: false },
        tooltip: { enabled: false },
        plotOptions: { pie: { donut: { size: '72%' } } },
        stroke: { width: 0 },
    });
    window._overallChart.render();
}

// ==========================================
//  Fill Payment Form on Row Click
// ==========================================
function fillPaymentForm(rowIdx) {
    if (!_tourData) return;
    const row = _tourData.payment_rows[rowIdx];
    const t   = _tourData.tour_info;

    document.getElementById('pay_tour_id').value      = t.tour_id;
    document.getElementById('pay_manual_id').value    = t.manual_id;
    document.getElementById('pay_supplier_acc').value = row.supplier_acc;
    document.getElementById('pay_row_total').value    = row.total;
    document.getElementById('pay_supplier_name').value= row.supplier;
    document.getElementById('pay_amount').value       = row.balance > 0 ? row.balance : '';
    document.getElementById('pay_total_amt').textContent    = fmtNum(row.total);
    document.getElementById('pay_paid_so_far').textContent  = fmtNum(row.paid);
    document.getElementById('pay_remaining').textContent    = fmtNum(row.balance);

    const catSel = document.getElementById('pay_category');
    catSel.value = row.category;

    // scroll to panel
    document.querySelector('.tpd-make-payment').scrollIntoView({behavior:'smooth', block:'start'});
}

// ==========================================
//  Payment Request (for "Payment Not Requested" rows)
//  Mirrors tour/tour_accounts' own Request buttons — posts to the SAME
//  endpoints (payment_request / transport_payment_request) so hotel/activity/
//  driver-advance requests behave identically wherever they're submitted from.
// ==========================================
const tourAccountsBaseUrl = '<?= base_url('tour/tour_accounts/') ?>';

// ---- Hotel cost handoff (Hotel Rates panel → what we pay the hotel) ----
function _costBadgeCls(row) {
    if (row.cost_needs_check) return 'bg-danger';
    if (row.cost_offer && row.cost_offer.choice_needed) return 'bg-warning text-dark';
    if (row.cost_offer && (row.cost_offer.offers_used || []).length) return 'bg-success';
    if (row.cost_source === 'adhoc' || row.cost_source === 'mixed_adhoc') return 'bg-success';
    return 'bg-info text-dark';
}
function _hotelCostHtml(row, idx) {
    const plans = (row.cost_plans || []).join(', ');
    const nights = (row.cost_nights || []).map(n => `${n.date}: ${n.source === 'adhoc' ? 'ad hoc' : n.source === 'contracted' ? 'contract' : 'quoted'} $ ${fmtNum(n.pay_total)}${n.supplements > 0 ? ' (incl. supplements $ ' + fmtNum(n.supplements) + ')' : ''}`).join('\n');
    let html = `<div class="mt-1" style="font-size:.78rem; white-space:nowrap;" title="${esc(nights)}"><span class="text-muted">Hotel cost</span> <span class="fw-semibold">$ ${fmtNum(row.cost_dollar)}</span> <span class="badge ${_costBadgeCls(row)}">${esc(row.cost_source_label || '')}</span></div>`;
    if (row.cost_margin !== null && row.cost_margin !== undefined) {
        html += `<div class="${row.cost_margin < 0 ? 'text-danger' : 'text-muted'}" style="font-size:.72rem;">margin $ ${fmtNum(row.cost_margin)}${row.cost_supplements > 0 ? ' · supplements $ ' + fmtNum(row.cost_supplements) : ''}${plans ? ' · ' + esc(plans) : ''}</div>`;
    }
    if (row.cost_needs_check) {
        html += `<div class="text-danger" style="font-size:.72rem; white-space:normal; max-width:260px;"><i class="ri-error-warning-line"></i> ${esc((row.cost_check_notes || []).join(' '))}</div>`;
    }
    html += _offerCompactHtml(row, idx);
    return html;
}
// ---- tf-offers-r1: what the hotel is paid, beside the published rate, and the special rates to decide ----
function _offerFitting(o) { return (o.offer_options || []).filter(x => x.nights_fit > 0); }
// what a special rate saves or costs more: against the published rate, or — with no published rate on file — against
// the quoted figure the stay is paid at meanwhile (tf-offers-r3)
function _offerDiffTxt(o, row, x) {
    let d = null, against = '';
    if (x.saving !== null && x.saving !== undefined) { d = x.saving; }
    else if (row && row.total_dollar !== null && row.total_dollar !== undefined) { d = Math.round((row.total_dollar - x.total_if_used) * 100) / 100; against = ' against the quoted figure'; }
    if (d === null) return '';
    return (d >= 0 ? 'saves $ ' : 'costs more by $ ') + fmtNum(Math.abs(d)) + against;
}
function _offerCompactHtml(row, idx) {
    const o = row.cost_offer; if (!o) return '';
    let h = '';
    if (o.pub_total !== null && o.pub_total !== undefined && (o.offers_used || []).length) {
        h += `<div class="text-muted" style="font-size:.72rem;">Published $ ${fmtNum(o.pub_total)}${o.saving ? ` · <span class="${o.saving > 0 ? 'text-success' : 'text-danger'}">saving $ ${fmtNum(o.saving)}</span>` : ''}</div>`;
    }
    (o.offers_used || []).forEach(u => { h += `<div style="font-size:.72rem; color:#065f46; white-space:normal; max-width:260px;"><i class="ri-price-tag-3-line"></i> ${esc(OFFER_KIND[u.kind] || 'Offer')} applied: ${esc(u.name)}${u.nights < o.nights ? ` (${u.nights} of ${o.nights} nights)` : ''}</div>`; });
    const fitting = _offerFitting(o);
    if (o.choice_needed) {
        // tf-offers-r3: no special rate is proposed — the published rate stays until an authorized person selects one
        h += `<div class="text-warning fw-semibold" style="font-size:.72rem; white-space:normal; max-width:260px;"><i class="ri-flag-line"></i> ${fitting.length} special rate${fitting.length === 1 ? '' : 's'} available — published rate used until you decide</div>`;
    } else if (o.choice && o.choice.choice === 'published' && fitting.length) {
        h += `<div class="text-muted" style="font-size:.72rem;">Published rate kept by ${esc(o.choice.chosen_by)}</div>`;
    }
    if (o.choice && o.choice.choice === 'offer') {
        h += `<div class="text-muted" style="font-size:.7rem;">applied by ${esc(o.choice.chosen_by)} · ${esc(String(o.choice.chosen_at || '').slice(0, 16))}</div>`;
    }
    if (fitting.length && CAN_CHOOSE_OFFER && idx !== undefined) {
        h += `<a href="#" class="small" style="font-size:.72rem;" onclick="chooseOfferRate(${idx}); return false;"><i class="ri-exchange-line"></i> ${o.choice_needed ? 'Review special rate' : 'Change the payment rate'}</a>`;
    }
    return h;
}
// The payment step (tf-offers-r1): published rate, the special rate(s) available, the final amount to pay — the
// authorized user picks the rate; the pick fills the request amount, which stays theirs to confirm or correct.
function _offerCurrentPick(o) {
    // tf-offers-r3: the published rate is the default — a special rate is used only when an authorized person selects it
    // (an earlier decision on file is shown as it was made)
    if (o.choice) return o.choice.choice === 'published' ? 'published' : String(o.choice.contract_id);
    return 'published';
}
function _offerBlockHtml(row, idx) {
    const o = row.cost_offer; if (!o) return '';
    const fitting = _offerFitting(o);
    if (!fitting.length && !(o.offers_used || []).length) return '';   // no special rate: the payment goes ahead as before
    const pubTxt = o.pub_total !== null && o.pub_total !== undefined ? '$ ' + fmtNum(o.pub_total) : null;
    const pubAmount = o.pub_total !== null && o.pub_total !== undefined ? o.pub_total : (row.total_dollar ?? o.pay_total);
    const pick = _offerCurrentPick(o);
    const radio = (val, amount, title, sub) => `<label class="d-flex gap-2 align-items-start border rounded p-2 mb-1" style="cursor:pointer;"><input type="radio" name="pr_rate_pick" value="${val}" data-amount="${amount}" ${pick === val ? 'checked' : ''} class="mt-1" onchange="offerPickChanged()"><span class="flex-grow-1"><span class="d-flex justify-content-between gap-2"><b>${title}</b><b>$ ${fmtNum(amount)}</b></span><span class="text-muted" style="font-size:.72rem;">${sub}</span></span></label>`;
    let h = `<div class="border rounded p-2" style="background:#fff; font-size:.8rem;" id="pr_offer_block">`;
    h += `<div class="fw-semibold mb-1"><i class="ri-lock-line"></i> Special rate — confidential (authorized users only)</div>`;
    h += radio('published', pubAmount, 'Published contract rate', pubTxt ? 'the rate the quotation was built on' : 'no published rate on file — the quoted figure');
    fitting.forEach(x => {
        h += radio(String(x.plan_id), x.total_if_used, x.kind === 'early_bird' ? 'Available early bird rate' : 'Available tactical rate',
            `${esc(x.name)}${x.rate_code ? ' · ' + esc(x.rate_code) : ''} · ${x.nights_fit} of ${o.nights} night${o.nights === 1 ? '' : 's'}${_offerDiffTxt(o, row, x) ? ' · ' + _offerDiffTxt(o, row, x) : ''}${x.book_to ? ' · book by ' + esc(x.book_to) : ''}`
            + ` · <a href="#" class="offer-terms-link" data-plan="${x.plan_id}" onclick="return _offerTermsFromPanel(event, ${x.plan_id}, ${idx});">View offer</a>`);
    });
    h += `<div class="d-flex justify-content-between mt-1"><span class="fw-semibold">Final amount to pay the hotel</span><span class="fw-bold" id="pr_final_amt">-</span></div>`;
    h += `<div class="d-flex justify-content-between text-muted" style="font-size:.72rem;"><span>Internal margin (quoted $ ${fmtNum(row.total_dollar ?? 0)} − final)</span><span id="pr_final_margin">-</span></div>`;
    h += `<input type="text" id="pr_offer_note" class="form-control form-control-sm mt-1" placeholder="Note on the rate used (optional)">`;
    h += `<div class="text-muted mt-1" style="font-size:.72rem;">Changes only what the hotel is paid and our internal saving — never the client quotation or selling price.${o.choice ? ` Last decided by ${esc(o.choice.chosen_by)} on ${esc(String(o.choice.chosen_at || '').slice(0, 16))}.` : ''}</div>`;
    if ((o.history || []).length) {
        h += `<details class="mt-1"><summary style="font-size:.72rem;" class="text-muted">History (${o.history.length})</summary>${o.history.map(e => `<div style="font-size:.7rem;" class="text-muted">${esc(String(e.created_at).slice(0, 16))} · ${esc(e.by_user)} — ${esc(e.label || e.event)}${e.offer_name ? ': ' + esc(e.offer_name) : ''}${e.event === 'reviewed' ? '' : ' · paid $ ' + fmtNum(e.used_total) + (e.published_total !== null ? ' (published $ ' + fmtNum(e.published_total) + ')' : '')}${e.note ? ' — ' + esc(e.note) : ''}</div>`).join('')}</details>`;
    }
    return h + '</div>';
}
function offerPickChanged() {
    const sel = document.querySelector('input[name=pr_rate_pick]:checked'); if (!sel) return;
    const amount = parseFloat(sel.dataset.amount);
    document.getElementById('pr_amount').value = amount > 0 ? amount : '';
    document.getElementById('pr_final_amt').textContent = '$ ' + fmtNum(amount);
    const row = _tourData && _tourData.payment_rows[document.getElementById('pr_row_idx').value];
    const m = row ? (row.total_dollar ?? 0) - amount : null;
    const el = document.getElementById('pr_final_margin'); if (el && m !== null) { el.textContent = '$ ' + fmtNum(m); el.className = m < 0 ? 'text-danger' : ''; }
    updatePaymentRequestLkrPreview();
}
// The authorized user's review before a hotel payment with a special rate available: the notice, then the special
// rate itself; "Proceed to Payment" opens only once they have reviewed it.
const _offerReviewed = {};
// ---- tf-offers-r3: hotel payments held for the System Administrator's rate review ----
let _releaseMode = null;
async function loadRateReviewQueue() {
    const box = document.getElementById('rate_review_queue');
    if (!box || !CAN_CHOOSE_OFFER) return;
    let j;
    try { j = await (await fetch(OFFER_HOLDS_URL, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })).json(); } catch (e) { j = null; }
    const list = (j && j.status === 200 && j.data) || [];
    if (!list.length) { box.style.display = 'none'; box.innerHTML = ''; return; }
    box.innerHTML = `<div class="card-body p-3"><div class="fw-semibold mb-2"><i class="ri-time-line me-1"></i> Hotel payments awaiting your rate review (${list.length})</div>`
        + `<div class="table-responsive"><table class="table table-sm align-middle mb-0" style="font-size:.85rem;"><thead><tr><th>Tour</th><th>Hotel</th><th>Prepared by</th><th>Prepared on</th><th class="text-end">Amount</th><th></th></tr></thead><tbody>`
        + list.map(h => `<tr class="rate-review-item"><td>${esc(h.manual_id || h.tour_id)}</td><td>${esc(h.hotel_name || h.hotel_acc)}</td><td>${esc(h.prepared_by)}</td><td>${esc(String(h.prepared_at || '').slice(0, 16))}</td><td class="text-end">$ ${fmtNum(h.request_amount)}</td>`
            + `<td class="text-end"><button class="btn btn-sm btn-outline-primary py-0" onclick="_openHeldTour('${esc(h.manual_id || '')}')">Open</button></td></tr>`).join('')
        + `</tbody></table></div><div class="text-muted small mt-1">Prepared by payment staff for stays with a tactical or early bird. Review the rate, then release each to Payment approval.</div></div>`;
    box.style.display = 'block';
}
function _openHeldTour(mid) {
    if (!mid) return;
    document.getElementById('manual_id_input').value = mid;
    searchTour();
}
// "Review & release": the notice and the review (special rates still undecided), then the payment panel in release mode
async function openRateReview(idx) {
    if (!_tourData) return;
    const row = _tourData.payment_rows[idx]; const rr = row && row.rate_review; const o = row && row.cost_offer;
    if (!rr || !rr.hold_id) return;
    if (o && o.choice_needed > 0 && _offerFitting(o).length) {
        const key = _tourData.tour_info.tour_id + '|' + o.hotel_acc;
        if (!_offerReviewed[key]) {
            if (!await _reviewSpecialRate(row)) return;
            _offerReviewed[key] = true;
        }
    }
    _releaseMode = { idx, hold: rr };
    _showPaymentRequestModal(idx);
    const info = document.getElementById('pr_release_info');
    info.innerHTML = `<div class="fw-semibold"><i class="ri-time-line"></i> Waiting for your rate review</div>`
        + `<div>Prepared by <b>${esc(rr.prepared_by)}</b> on ${esc(String(rr.prepared_at || '').slice(0, 16))} for <b>$ ${fmtNum(rr.amount)}</b>${rr.bank_name ? ' · ' + esc(rr.bank_name) : ''}${rr.sup_bank_acc ? ' ' + esc(rr.sup_bank_acc) : ''}</div>`
        + `<div class="text-muted">Choose the rate and confirm the final amount. "Release to Payment approval" makes the payment request for ${esc(rr.prepared_by)} at that amount.</div>`;
    info.style.display = 'block';
    // the published rate stays selected; the amount starts as prepared (within the quoted figure)
    if (rr.amount && document.querySelector('input[name=pr_rate_pick]:checked') && document.querySelector('input[name=pr_rate_pick]:checked').value === 'published') {
        document.getElementById('pr_amount').value = rr.amount;
        if (typeof updatePaymentRequestLkrPreview === 'function') updatePaymentRequestLkrPreview();
    }
    document.getElementById('pr_submit_btn').innerHTML = '<i class="ri-check-double-line me-1"></i> Release to Payment approval';
    document.getElementById('pr_return_btn').style.display = 'inline-block';
}
function _endReleaseMode() {
    _releaseMode = null;
    const info = document.getElementById('pr_release_info'); if (info) { info.style.display = 'none'; info.innerHTML = ''; }
    const rb = document.getElementById('pr_return_btn'); if (rb) rb.style.display = 'none';
    const sb = document.getElementById('pr_submit_btn'); if (sb) sb.innerHTML = '<i class="ri-send-plane-line me-1"></i> Send Request';
}
document.addEventListener('DOMContentLoaded', function () {
    const m = document.getElementById('paymentRequestModal');
    if (m) m.addEventListener('hidden.bs.modal', _endReleaseMode);
});
async function _releaseHeldPayment() {
    const rm = _releaseMode; if (!rm) return;
    const row = _tourData.payment_rows[rm.idx];
    let amount = parseFloat(String(document.getElementById('pr_amount').value).replace(/,/g, ''));
    if (isNaN(amount) || amount <= 0) { Swal.fire('Warning', 'Please enter a valid amount greater than zero.', 'warning'); return; }
    // the same ceiling as any payment request: the quoted figure or the hotel cost payable now (checked again by the server)
    const sel = document.querySelector('input[name=pr_rate_pick]:checked');
    const pick = sel ? sel.value : 'published';
    const ceiling = Math.max(row.total_dollar ?? row.total ?? 0, row.cost_dollar ?? 0);
    if (ceiling > 0 && amount > ceiling) { Swal.fire('Amount Exceeded!', `Request amount ($ ${fmtNum(amount)}) cannot exceed the Q Amount / hotel cost ($ ${fmtNum(ceiling)}).`, 'error'); return; }
    const body = { hold_id: rm.hold.hold_id, choice: pick, amount, note: (document.getElementById('pr_offer_note') || {}).value || '' };
    const btn = document.getElementById('pr_submit_btn'); btn.disabled = true;
    let j;
    try {
        const res = await fetch(OFFER_RELEASE_URL, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify(body) });
        try { j = await res.json(); } catch (e) { j = { status: res.status }; }
    } catch (e) { j = { status: 0 }; }
    btn.disabled = false;
    if (j.status !== 200) { Swal.fire('Not released', j.message || 'The payment could not be released.', 'error'); return; }
    bootstrap.Modal.getInstance(document.getElementById('paymentRequestModal')).hide();
    await Swal.fire('Released', j.message || 'Released to Payment approval.', 'success');
    loadRateReviewQueue(); searchTour();
}
async function returnHeldPayment() {
    const rm = _releaseMode; if (!rm) return;
    const holdId = rm.hold.hold_id, who = rm.hold.prepared_by;
    bootstrap.Modal.getInstance(document.getElementById('paymentRequestModal')).hide();
    const r = await Swal.fire({ title: 'Return to staff', input: 'textarea', inputLabel: `Back to ${who} — nothing is requested. Why?`, inputPlaceholder: 'e.g. bank details to correct', showCancelButton: true, confirmButtonText: 'Return' });
    if (!r.isConfirmed) return;
    let j;
    try {
        const res = await fetch(OFFER_RETURN_URL, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify({ hold_id: holdId, note: r.value || '' }) });
        try { j = await res.json(); } catch (e) { j = { status: res.status }; }
    } catch (e) { j = { status: 0 }; }
    if (j.status !== 200) { Swal.fire('Not returned', j.message || 'The payment could not be returned.', 'error'); return; }
    await Swal.fire('Returned', j.message || 'Returned to staff.', 'success');
    loadRateReviewQueue(); searchTour();
}
async function _reviewSpecialRate(row) {
    const o = row.cost_offer;
    const n = await Swal.fire({ icon: 'info', title: 'Special rate available', text: 'A Tactical / Early Bird Rate is available for this booking. Please review the special rate before proceeding with payment.',
        showCancelButton: true, confirmButtonText: 'Review the special rate', cancelButtonText: 'Not now' });
    if (!n.isConfirmed) return false;
    const stay = (o.nights_detail || []);
    const stayTxt = stay.length ? `${esc(stay[0].date)}${stay.length > 1 ? ' – ' + esc(stay[stay.length - 1].date) : ''} · ${o.nights} night${o.nights === 1 ? '' : 's'} · ${esc(stay[0].room_type || '')} ${esc(stay[0].meal_plan || '')}` : '';
    let ticked = false; const viewed = [];
    // tf-offers-r3: "View offer" opens the offer's terms and comes back to this review, the tick kept
    for (;;) {
        let viewId = null;
        window._offerViewFromReview = (id) => { viewId = id; Swal.close(); return false; };
        let html = `<div class="text-start" style="font-size:.85rem;">`;
        html += `<div class="text-muted small mb-2">${esc(row.supplier || '')} · ${stayTxt}<br><i class="ri-lock-line"></i> Confidential — authorized users only. It changes only what the hotel is paid, never the client quotation or selling price.</div>`;
        html += `<div class="d-flex justify-content-between border-bottom py-1"><span>Published contract rate${o.pub_total !== null && o.pub_total !== undefined ? '' : ' <span class="text-muted small">(none on file — the quoted figure is paid)</span>'}</span><b style="white-space:nowrap;">$ ${fmtNum(o.pub_total !== null && o.pub_total !== undefined ? o.pub_total : (row.total_dollar ?? o.pay_total))}</b></div>`;
        _offerFitting(o).forEach(x => {
            html += `<div class="border rounded p-2 mt-2"><div class="d-flex justify-content-between gap-2"><b>${esc(OFFER_KIND[x.kind] || 'Special rate')}: ${esc(x.name)}</b><b style="white-space:nowrap;">$ ${fmtNum(x.total_if_used)}</b></div>`
                + `<div class="small text-muted">${x.rate_code ? 'Reference ' + esc(x.rate_code) + ' · ' : ''}${x.nights_fit} of ${o.nights} night${o.nights === 1 ? '' : 's'} fit${_offerDiffTxt(o, row, x) ? ' · ' + _offerDiffTxt(o, row, x) : ''}${x.book_to ? ' · booking deadline ' + esc(x.book_to) : ''}</div>`
                + `<button type="button" class="btn btn-sm btn-outline-secondary mt-1 py-0 offer-view-btn" data-plan="${x.plan_id}" onclick="_offerViewFromReview(${x.plan_id})"><i class="ri-file-list-3-line"></i> View offer</button></div>`;
        });
        const notFit = (o.offer_options || []).filter(x => x.nights_fit === 0);
        if (notFit.length) html += `<div class="small text-muted mt-2">Not applicable: ${notFit.map(x => `${esc(x.name)}${x.why ? ' (' + esc(x.why) + ')' : ''}`).join('; ')}</div>`;
        html += `<div class="small mt-2">The published rate stays selected on the payment step. A special rate is used only if you select it there.</div>`;
        html += `<label class="d-flex gap-2 align-items-center mt-3"><input type="checkbox" id="offer_reviewed"${ticked ? ' checked' : ''}> <span>I have reviewed the special rate</span></label></div>`;
        const r = await Swal.fire({ title: 'Review the special rate', html, width: 640, showCancelButton: true, confirmButtonText: 'Proceed to Payment', cancelButtonText: 'Cancel',
            didOpen: () => { const b = Swal.getConfirmButton(); b.disabled = !ticked; document.getElementById('offer_reviewed').addEventListener('change', e => { ticked = e.target.checked; b.disabled = !ticked; }); },
            preConfirm: () => document.getElementById('offer_reviewed').checked || (Swal.showValidationMessage('Tick "I have reviewed the special rate" first'), false) });
        if (viewId) {
            const x = _offerFitting(o).find(f => f.plan_id === viewId);
            if (x && !viewed.includes(x.name)) viewed.push(x.name);
            await _showOfferTerms(viewId, row, 'Back to the review');
            continue;
        }
        if (!r.isConfirmed) return false;
        return await _recordOfferReview(row, viewed);
    }
}
// tf-offers-r3: the review is kept with who and when (whatever is decided on the payment step); if it cannot be
// recorded, the payment does not open
async function _recordOfferReview(row, viewed) {
    const o = row.cost_offer;
    let j;
    try {
        const res = await fetch(OFFER_REVIEWED_URL, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify({ tour_id: _tourData.tour_info.tour_id, hotel_acc: o.hotel_acc, viewed: viewed || [] }) });
        try { j = await res.json(); } catch (e) { j = { status: res.status }; }
    } catch (e) { j = { status: 0 }; }
    if (j.status !== 200) { await Swal.fire('Review not recorded', (j.message || 'The review could not be recorded.') + ' The payment was not opened — try again.', 'error'); return false; }
    if (j.data) { o.history = [j.data].concat(o.history || []); }
    return true;
}
// The terms of one tactical / early bird (tf-offers-r3): stay and booking windows, markets, minimum stay, the rates
// (the stay's room and meal plan marked), supplements, restrictions and conditions. "Back" returns where it was opened.
async function _showOfferTerms(planId, row, backLabel) {
    let j;
    try {
        const res = await fetch(OFFER_TERMS_URL + encodeURIComponent(planId), { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        try { j = await res.json(); } catch (e) { j = { status: res.status }; }
    } catch (e) { j = { status: 0 }; }
    if (j.status !== 200 || !j.data) { await Swal.fire({ icon: 'error', title: 'Offer terms', text: j.message || 'The offer terms could not be loaded.', confirmButtonText: backLabel || 'Back' }); return; }
    await Swal.fire({ title: esc(j.data.kind_label || 'Special rate'), html: _offerTermsHtml(j.data, row), width: 820, confirmButtonText: backLabel || 'Back to the payment', customClass: { popup: 'offer-terms-popup' } });
}
function _offerTermsFromPanel(ev, planId, idx) {
    if (ev) { ev.preventDefault(); ev.stopPropagation(); }
    const row = _tourData && _tourData.payment_rows[idx];
    _showOfferTerms(planId, row, 'Back to the payment');
    return false;
}
function _offerTermsHtml(t, row) {
    const o = row && row.cost_offer; const stay = (o && o.nights_detail) || [];
    const norm = v => String(v || '').toLowerCase().replace(/\s+room$/, '').trim();
    const isStay = r => stay.some(n => norm(n.room_type) === norm(r.room_type) && String(n.meal_plan || '').toUpperCase() === String(r.meal_plan || '').toUpperCase());
    const d = v => v ? esc(v) : '—';
    const money = v => v === null || v === undefined || v === '' ? '—' : fmtNum(v);
    const STATUS = { active: 'Active', deadline_soon: 'Booking deadline soon', booking_closed: 'Booking closed', expired: 'Expired', withdrawn: 'Withdrawn', draft: 'Draft', upcoming: 'Booking opens later' };
    let h = `<div class="text-start" style="font-size:.82rem;">`;
    h += `<div class="fw-semibold mb-1" style="font-size:.95rem;">${esc(t.name)}</div>`;
    h += `<div class="text-muted small mb-2">${esc((t.hotel && t.hotel.name) || '')}${t.rate_code ? ' · Reference ' + esc(t.rate_code) : ''} · ${esc(STATUS[t.offer_status] || t.offer_status || '')}<br><i class="ri-lock-line"></i> Confidential — authorized users only.</div>`;
    const kv = (k, v) => `<div class="d-flex gap-2 border-bottom py-1"><span class="text-muted" style="min-width:150px;">${k}</span><span>${v}</span></div>`;
    h += kv('Stay dates', `${d(t.valid_from)} – ${d(t.valid_to)}`);
    h += kv('Booking window', t.book_from || t.book_to ? `${d(t.book_from)} – ${d(t.book_to)}` : 'any booking date');
    h += kv('Markets', t.markets && t.markets.length ? t.markets.map(esc).join(', ') : 'all markets');
    h += kv('Minimum stay', t.min_stay ? `${t.min_stay} night${t.min_stay === 1 ? '' : 's'}` : 'none');
    h += kv('Prices', `${esc(t.currency || '')} ${t.pricing_mode === 'per_person' ? 'per person' : 'per room'} per night${t.taxes_included === false ? ', taxes extra' : (t.taxes_included ? ', taxes included' : '')}`);
    // what this offer gives THIS stay, night by night, as the payment works it out
    const mine = ((row && row.cost_nights) || []).map(n => ({ n, op: (n.options || []).find(x => Number(x.plan_id) === Number(t.id)) })).filter(x => x.op);
    if (mine.length) {
        h += `<div class="mt-2 fw-semibold">For this stay</div><div style="overflow-x:auto;"><table class="table table-sm mb-1 offer-terms-thisstay" style="font-size:.78rem;"><thead><tr><th>Night</th><th>Room · meal plan</th><th class="text-end">Single / Double / Triple</th><th class="text-end">Night total</th><th></th></tr></thead><tbody>`;
        let tot = 0;
        mine.forEach(({ n, op }) => {
            if (op.ok) tot += Number(op.total || 0);
            h += `<tr><td>${esc(n.date)}</td><td>${esc(n.room_type || '')} ${esc(n.meal_plan || '')}</td><td class="text-end">${(op.rates || []).map(money).join(' / ')}</td><td class="text-end">${op.ok ? money(op.total) : '—'}</td><td class="small text-muted">${op.ok ? '' : esc(op.why || 'does not apply')}</td></tr>`;
        });
        h += `</tbody><tfoot><tr><th colspan="3">Total for the nights it fits</th><th class="text-end">${money(tot)}</th><th></th></tr></tfoot></table></div>`;
    }
    (t.seasons || []).forEach(se => {
        h += `<div class="mt-2 fw-semibold">${esc(se.name || 'Season')} <span class="text-muted fw-normal small">${(se.dates || []).map(x => d(x.from) + ' – ' + d(x.to)).join(', ')}${se.min_stay ? ' · minimum ' + se.min_stay + ' nights' : ''}</span></div>`;
        h += `<div style="overflow-x:auto;"><table class="table table-sm mb-1" style="font-size:.78rem;"><thead><tr><th>Room</th><th>Meal plan</th><th class="text-end">Single</th><th class="text-end">Double</th><th class="text-end">Triple</th></tr></thead><tbody>`;
        (se.rates || []).forEach(r => {
            const mine = isStay(r);
            h += `<tr${mine ? ' class="table-warning offer-terms-stay"' : ''}><td>${esc(r.room_type)}${mine ? ' <span class="badge bg-warning text-dark">this stay</span>' : ''}</td><td>${esc(r.meal_plan)}</td><td class="text-end">${money(r.sgl)}</td><td class="text-end">${money(r.dbl)}</td><td class="text-end">${money(r.tpl)}</td></tr>`;
        });
        h += `</tbody></table></div>`;
    });
    if ((t.room_supplements || []).length) {
        h += `<div class="mt-2 fw-semibold">Room supplements</div>` + t.room_supplements.map(x => { const me = stay.some(n => norm(n.room_type) === norm(x.room_type)); return `<div class="small${me ? ' offer-terms-stay-room' : ''}">${esc(x.room_type)} + ${fmtNum(x.amount)}${x.per ? ' per ' + esc(x.per) : ''}${x.season ? ' (' + esc(x.season) + ')' : ''}${me ? ' <span class="badge bg-warning text-dark">this stay</span>' : ''}${x.note ? ' — ' + esc(x.note) : ''}</div>`; }).join('');
    }
    if ((t.meal_deltas || []).length) {
        h += `<div class="mt-2 fw-semibold">Meal plan supplements</div>` + t.meal_deltas.map(x => `<div class="small">${esc(x.from)} → ${esc(x.to)} + ${fmtNum(x.adult)} per adult${x.child !== null && x.child !== undefined ? ', ' + fmtNum(x.child) + ' per child' : ''}${x.season ? ' (' + esc(x.season) + ')' : ''}${x.note ? ' — ' + esc(x.note) : ''}</div>`).join('');
    }
    if ((t.supplements || []).length) {
        h += `<div class="mt-2 fw-semibold">Supplements</div>` + t.supplements.map(x => `<div class="small">${esc(x.name)}${x.compulsory ? ' <b>(compulsory)</b>' : ''}${x.date_from ? ' · ' + d(x.date_from) + (x.date_to && x.date_to !== x.date_from ? ' – ' + d(x.date_to) : '') : ''}${x.amount !== null && x.amount !== undefined ? ' · ' + fmtNum(x.amount) + (x.per ? ' per ' + esc(x.per) : '') : ''}${x.note ? ' — ' + esc(x.note) : ''}</div>`).join('');
    }
    if ((t.restrictions || []).length) {
        h += `<div class="mt-2 fw-semibold">Restrictions</div>` + t.restrictions.map(x => `<div class="small">${esc(String(x.kind || '').replace(/_/g, ' '))}${x.date_from ? ' · ' + d(x.date_from) + (x.date_to && x.date_to !== x.date_from ? ' – ' + d(x.date_to) : '') : ''}${x.value ? ' · ' + esc(x.value) : ''}${x.note ? ' — ' + esc(x.note) : ''}</div>`).join('');
    }
    (t.conditions || []).forEach(c => { h += `<div class="mt-2 fw-semibold">${esc(c.label)}</div><div class="small" style="white-space:pre-wrap;">${esc(c.text)}</div>`; });
    h += `<div class="text-muted small mt-2">${t.source_file ? 'Source: ' + esc(t.source_file) + ' · ' : ''}${t.approved_by ? 'approved by ' + esc(t.approved_by) + (t.approved_at ? ' on ' + esc(String(t.approved_at).slice(0, 10)) : '') : ''}</div>`;
    return h + '</div>';
}
async function openPaymentRequestModal(rowIdx) {
    if (!_tourData) return;
    const row = _tourData.payment_rows[rowIdx];
    const o = row && row.category === 'Hotel' ? row.cost_offer : null;
    // a special rate nobody has decided on yet: the notice and the review come first (authorized users only get cost_offer)
    if (o && CAN_CHOOSE_OFFER && o.choice_needed > 0 && _offerFitting(o).length) {
        const key = _tourData.tour_info.tour_id + '|' + o.hotel_acc;
        if (!_offerReviewed[key]) {
            if (!await _reviewSpecialRate(row)) return;
            _offerReviewed[key] = true;
        }
    }
    _showPaymentRequestModal(rowIdx);
}
// saves the rate picked on the payment step as the decision for the stay (who and when kept), before the request
async function _saveOfferPick(row) {
    const o = row.cost_offer; const sel = document.querySelector('input[name=pr_rate_pick]:checked');
    if (!o || !sel) return true;
    const pick = sel.value;
    const current = o.choice ? (o.choice.choice === 'published' ? 'published' : String(o.choice.contract_id)) : null;
    if (pick === current) return true;
    let j;
    try {
        const res = await fetch(OFFER_CHOOSE_URL, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify({ tour_id: _tourData.tour_info.tour_id, hotel_acc: o.hotel_acc, choice: pick, note: (document.getElementById('pr_offer_note') || {}).value || '' }) });
        try { j = await res.json(); } catch (e) { j = { status: res.status }; }
    } catch (e) { j = { status: 0 }; }
    if (j.status !== 200) { Swal.fire('Not saved', j.message || 'The rate decision could not be saved — the payment request was not sent.', 'error'); return false; }
    return true;
}
async function chooseOfferRate(idx) {
    if (!_tourData) return;
    const row = _tourData.payment_rows[idx]; const o = row && row.cost_offer; if (!o) return;
    const prm = document.getElementById('paymentRequestModal'); const inst = prm && window.bootstrap && bootstrap.Modal.getInstance(prm); if (inst) inst.hide();
    // preselected: the decision on file; with none, the published rate (tf-offers-r3: no special rate is ever preselected)
    const cur = o.choice ? (o.choice.choice === 'published' ? 'published' : String(o.choice.contract_id)) : 'published';   // tf-offers-r3: published by default
    const opt = (val, title, sub, disabled) => `<label class="d-flex gap-2 align-items-start border rounded p-2 mb-1 text-start" style="cursor:${disabled ? 'default' : 'pointer'}; font-size:.85rem;${disabled ? ' opacity:.55;' : ''}"><input type="radio" name="offer_pick" value="${val}" ${cur === val && !disabled ? 'checked' : ''} ${disabled ? 'disabled' : ''} class="mt-1"><span><b>${title}</b><br><span class="small text-muted">${sub}</span></span></label>`;
    let html = `<div class="text-start small text-muted mb-2">${esc(row.supplier || '')} · ${o.nights} night${o.nights === 1 ? '' : 's'}. Only what the hotel is paid — the customer quotation does not change.</div>`;
    html += opt('published', `Published / contract rate — $ ${o.pub_total !== null && o.pub_total !== undefined ? fmtNum(o.pub_total) : fmtNum(o.pay_total) + ' (quoted figure — no published rate on file)'}`, 'keep the rate the quotation was built on');
    (o.offer_options || []).forEach(x => {
        const fits = x.nights_fit > 0;
        html += opt(String(x.plan_id), `Apply ${esc(OFFER_KIND[x.kind] || 'Offer')}: ${esc(x.name)}${fits ? ' — $ ' + fmtNum(x.total_if_used) : ''}`,
            fits ? `${x.nights_fit} of ${o.nights} night${o.nights === 1 ? '' : 's'} fit${x.saving !== null && x.saving !== undefined ? (x.saving >= 0 ? ' · saves $ ' : ' · costs more by $ ') + fmtNum(Math.abs(x.saving)) : ''}${x.rate_code ? ' · ' + esc(x.rate_code) : ''}${x.book_to ? ' · book by ' + esc(x.book_to) : ''}`
                 : `does not fit this stay${x.why ? ': ' + esc(x.why) : ''}`, !fits);
    });
    html += `<textarea id="offer_note" class="form-control form-control-sm mt-2" rows="2" placeholder="Note (optional) — why this rate"></textarea>`;
    const r = await Swal.fire({ title: 'Rate for the hotel payment', html, width: 680, showCancelButton: true, confirmButtonText: 'Use this rate',
        preConfirm: () => { const v = document.querySelector('input[name=offer_pick]:checked'); if (!v) { Swal.showValidationMessage('Pick a rate'); return false; } return { choice: v.value, note: (document.getElementById('offer_note') || {}).value || '' }; } });
    if (!r.isConfirmed || !r.value) return;
    let j;
    try {
        const res = await fetch(OFFER_CHOOSE_URL, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify({ tour_id: _tourData.tour_info.tour_id, hotel_acc: o.hotel_acc, choice: r.value.choice, note: r.value.note }) });
        try { j = await res.json(); } catch (e) { j = { status: res.status }; }
        if (res.status === 403 || j.status === 403) { j = { status: 403, message: 'Your user group does not hold "Tactical / Early Bird rate selection".' }; }
    } catch (e) { j = { status: 0, message: 'Could not save the decision.' }; }
    if (j.status === 200) { Swal.fire({ icon: 'success', text: j.message, timer: 2400, showConfirmButton: false }); searchTour(); }
    else { Swal.fire('Not saved', j.message || 'Could not save the decision.', 'error'); }
}
async function recalcHotelCosts() {
    if (!_tourData) return;
    const fd = new FormData(); fd.append('tour_id', _tourData.tour_info.tour_id);
    try {
        const r = await fetch('<?= base_url('tour/tour_payment_dashboard/recalc_hotel_cost') ?>', { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const j = await r.json();
        if (j.status === 200) { Swal.fire({ icon: 'success', text: j.message, timer: 1800, showConfirmButton: false }); searchTour(); } else { Swal.fire('Error', j.message || 'Could not recalculate', 'error'); }
    } catch (e) { Swal.fire('Error', 'Could not recalculate the hotel costs', 'error'); }
}

// The line under the supplier's name on a driver's / guide's row: the nickname and the category from the master
function _payeeLineHtml(row) {
    const p = row.payee;
    if (!p) return '';
    const bits = [];
    if (p.nickname) bits.push('“' + esc(p.nickname) + '”');
    if (p.category) bits.push('<span class="badge bg-primary">' + esc(p.category) + '</span>');
    if (p.status !== 'active' || p.archived) bits.push('<span class="badge bg-danger">' + (p.archived ? 'archived' : 'inactive') + '</span>');
    return bits.length ? '<div class="text-muted" style="font-size:.72rem;">' + bits.join(' · ') + '</div>' : '';
}
function _payeeBlockHtml(row) {
    const p = row.payee;
    if (!p) return '';
    const tour = _tourData && _tourData.tour_info ? (_tourData.tour_info.manual_id || _tourData.tour_info.tour_id || '') : '';
    const amount = 'LKR ' + fmtNum(row.total ?? 0);
    const mismatch = p.bank_visible && p.bank_account_no && row.supplier_bank_acc && String(p.bank_account_no).replace(/\s/g, '') !== String(row.supplier_bank_acc).replace(/\s/g, '');
    let h = `<div class="fw-semibold"><i class="ri-user-line"></i> Who is paid</div>`
        + `<div><b>${esc(p.name)}</b>${p.nickname ? ` · known as <b>“${esc(p.nickname)}”</b>` : ''}${p.category ? ` · <span class="badge bg-primary">${esc(p.category)}</span>${p.category_confirmed ? '' : ' <span class="badge bg-warning text-dark">category to confirm</span>'}` : ''}${p.status !== 'active' || p.archived ? ' <span class="badge bg-danger">' + (p.archived ? 'archived' : 'inactive') + ' on the master</span>' : ''}</div>`
        + `<div class="text-muted">Tour ${esc(tour)} · ${esc(row.category || '')} · amount ${amount}</div>`;
    if (p.bank_visible) {
        h += p.bank_account_no
            ? `<div>${[`Bank account name <b>${esc(p.bank_account_name || '(not on file)')}</b>`, [p.bank_name, p.bank_branch].filter(Boolean).map(esc).join(' '), `A/C <span class="font-monospace">${esc(p.bank_account_no)}</span>`].filter(Boolean).join(' · ')} ${p.bank_verified ? '<span class="badge bg-success">verified' + (p.bank_verified_at ? ' ' + esc(p.bank_verified_at) : '') + '</span>' : '<span class="badge bg-warning text-dark">not verified</span>'}</div>`
            : '<div class="text-warning">No bank account on the Driver &amp; Guide Master.</div>';
        if (mismatch) h += `<div class="text-danger">The account on the master (${esc(p.bank_account_no)}) differs from the supplier record (${esc(row.supplier_bank_acc)}) — check before paying.</div>`;
        if (p.payment_remarks) h += `<div class="text-muted">${esc(p.payment_remarks)}</div>`;
    }
    const w = (p.warnings || []).filter(x => x.level !== 'info');
    if (w.length) h += `<div class="text-warning">${w.map(x => esc(x.text)).join(' ')}</div>`;
    return h;
}
function _showPaymentRequestModal(rowIdx) {
    if (!_tourData) return;
    const row = _tourData.payment_rows[rowIdx];
    const isHotel = row.category === 'Hotel';
    // Hotel requests are made in $ (matching tour_accounts' Q Amount), everything
    // else is requested in LKR. A hotel request defaults to what the Hotel Rates
    // panel says we PAY (ad hoc → contract rate), not the customer's selling price.
    const hasCost = isHotel && row.cost_dollar !== null && row.cost_dollar !== undefined && row.cost_dollar > 0;
    const displayAmount = isHotel ? (hasCost ? row.cost_dollar : (row.total_dollar ?? 0)) : row.total;
    document.getElementById('pr_cost_row').style.display = hasCost ? 'flex' : 'none';
    document.getElementById('pr_cost_detail').style.display = hasCost ? 'block' : 'none';
    if (hasCost) {
        document.getElementById('pr_cost_amt').textContent = '$ ' + fmtNum(row.cost_dollar);
        const badge = document.getElementById('pr_cost_badge'); badge.className = 'badge ' + _costBadgeCls(row); badge.textContent = row.cost_source_label || '';
        document.getElementById('pr_cost_detail').innerHTML = `Quoted to the customer $ ${fmtNum(row.total_dollar ?? 0)} · margin $ ${fmtNum(row.cost_margin ?? 0)}${(row.cost_plans || []).length ? ' · ' + esc(row.cost_plans.join(', ')) : ''}${row.cost_needs_check ? '<br><span class="text-danger">' + esc((row.cost_check_notes || []).join(' ')) + '</span>' : ''}`;
    }

    const offerBox = document.getElementById('pr_offer_box');
    offerBox.innerHTML = isHotel && row.cost_offer ? _offerBlockHtml(row, rowIdx) : '';
    offerBox.style.display = offerBox.innerHTML ? 'block' : 'none';
    // tf-crew-master-r1: a driver's / guide's payment names who is paid — nickname and legal name side by side, the
    // category, and for the holders of "Driver & Guide bank details" the account name as the bank has it, the bank, the
    // account number — with the tour and the amount, so nobody pays the wrong person
    const payeeBox = document.getElementById('pr_payee_box');
    payeeBox.innerHTML = !isHotel ? _payeeBlockHtml(row) : '';
    payeeBox.style.display = payeeBox.innerHTML ? 'block' : 'none';

    document.getElementById('pr_row_idx').value = rowIdx;
    document.getElementById('pr_supplier_name').textContent = row.supplier || '-';
    document.getElementById('pr_category_lbl').textContent  = row.category || '-';
    // Total Amount stays the quoted figure (Q Amount); the request itself defaults to the payable cost
    document.getElementById('pr_total_amt').textContent     = (isHotel ? '$ ' : 'LKR ') + fmtNum(isHotel ? (row.total_dollar ?? 0) : row.total);
    document.getElementById('pr_amount_label').textContent  = (isHotel ? 'Request Amount ($) *' : 'Request Amount (LKR) *');
    document.getElementById('pr_amount').value = displayAmount > 0 ? displayAmount : '';
    if (document.querySelector('input[name=pr_rate_pick]')) offerPickChanged();   // tf-offers-r1: the rate picked sets the final amount

    // Today's USD rate — only relevant for Hotel, which posts in $ but is
    // converted to LKR server-side (payment_request()) at this same rate.
    // Force a fresh fetch here (not the cached value) so an edit/delete made
    // on the Daily USD Rate page is reflected right before the user submits.
    document.getElementById('pr_rate_row').style.display = isHotel ? 'flex' : 'none';
    document.getElementById('pr_lkr_row').style.display  = isHotel ? 'flex' : 'none';
    if (isHotel) {
        document.getElementById('pr_usd_rate').textContent = 'Refreshing...';
        loadUsdRate(true);
    }
    refreshPaymentRequestRateUi(isHotel);

    const modal = new bootstrap.Modal(document.getElementById('paymentRequestModal'));
    modal.show();
}

// Re-applies the current _todayUsdRate to the modal's rate row/missing-alert/
// LKR preview — called right after a (forced) loadUsdRate() resolves so the
// modal reflects the latest rate even if it was already open.
function refreshPaymentRequestRateUi(isHotel) {
    document.getElementById('pr_rate_missing_alert').style.display = (isHotel && !_todayUsdRate) ? 'block' : 'none';
    if (isHotel && _todayUsdRate) {
        document.getElementById('pr_usd_rate').textContent = '1 $ = LKR ' + fmtRate(_todayUsdRate);
    } else if (isHotel) {
        document.getElementById('pr_usd_rate').textContent = 'Not set';
    }
    updatePaymentRequestLkrPreview();
}

// Live LKR preview as the $ amount is edited — same formula payment_request()
// applies server-side (requestAmount * today's USD rate).
function updatePaymentRequestLkrPreview() {
    const rowIdx = document.getElementById('pr_row_idx').value;
    if (rowIdx === '' || !_tourData) return;
    const row = _tourData.payment_rows[rowIdx];
    if (row.category !== 'Hotel' || !_todayUsdRate) return;

    const amt = parseFloat(String(document.getElementById('pr_amount').value).replace(/,/g, ''));
    document.getElementById('pr_lkr_amt').textContent = !isNaN(amt) && amt > 0
        ? 'LKR ' + fmtNum(amt * _todayUsdRate)
        : '-';
}
document.getElementById('pr_amount').addEventListener('input', updatePaymentRequestLkrPreview);

async function submitPaymentRequest() {
    if (!_tourData) return;
    if (_releaseMode) return _releaseHeldPayment();   // tf-offers-r3: the System Administrator releasing a held payment
    const rowIdx = document.getElementById('pr_row_idx').value;
    const row    = _tourData.payment_rows[rowIdx];
    const t      = _tourData.tour_info;

    if (row.category === 'Hotel' && !_todayUsdRate) {
        Swal.fire('Warning', "Today's USD rate hasn't been entered yet. Please set it before requesting a hotel payment.", 'warning');
        return;
    }

    let requestAmount = document.getElementById('pr_amount').value;
    requestAmount = parseFloat(String(requestAmount).replace(/,/g, ''));
    if (isNaN(requestAmount) || requestAmount <= 0) {
        Swal.fire('Warning', 'Please enter a valid amount greater than zero.', 'warning');
        return;
    }

    // Can't request more than the quoted amount (Q Amount for Hotel, total for others) —
    // same ceiling tour_accounts enforces on its own Request button.
    const ceiling = row.category === 'Hotel' ? Math.max(row.total_dollar ?? row.total, row.cost_dollar ?? 0) : row.total;
    if (ceiling > 0 && requestAmount > ceiling) {
        const unit = row.category === 'Hotel' ? '$' : 'LKR';
        Swal.fire('Amount Exceeded!', `Request amount (${unit} ${fmtNum(requestAmount)}) cannot exceed the Q Amount / hotel cost (${unit} ${fmtNum(ceiling)}).`, 'error');
        return;
    }

    let url, payload;
    // Hotel amounts (totalHotelPay/dueAmount/requestAmount/tot_amt_doller) are all
    // in $ on tour_accounts — only the LKR posting is derived server-side from
    // requestAmount × the day's USD rate. Everything else stays LKR.
    const isHotel   = row.category === 'Hotel';
    const totalAmt  = isHotel ? (row.total_dollar ?? row.total) : row.total;
    const common = {
        tourId:   t.tour_id,
        manualId: t.manual_id,
        consignee: t.consignee || '',
        createUser: t.create_user || '',
        bankName:   row.bank_name || '',
        branchName: row.branch_name || '',
        supBankAcc: row.supplier_bank_acc || '',
        totalHotelPay:      totalAmt,
        totalHotelAdvance:  row.paid || 0,
        dueAmount:          totalAmt,
        requestAmount:      requestAmount,
        tot_amt_doller:     totalAmt,
    };

    if (isHotel) {
        url = tourAccountsBaseUrl + 'payment_request';
        payload = Object.assign({}, common, {
            hotelAcc:   row.supplier_acc,
            hotelName:  row.supplier,
            startDate:  t.start_date || '',
            endDate:    t.end_date || '',
            type:       'Hotel',
        });
    } else if (row.category === 'Activity') {
        url = tourAccountsBaseUrl + 'payment_request';
        payload = Object.assign({}, common, {
            hotelAcc:   row.supplier_acc,
            hotelName:  row.supplier,
            activityId: row.activity_id,
            type:       'lunch',
        });
    } else if (row.category === 'Driver Advance') {
        if (!row.driver_advance_request_id) {
            Swal.fire('Warning', 'No driver-submitted advance request found for this driver yet.', 'warning');
            return;
        }
        // Mirrors tour_accounts_note's "Advance Request" row / driver-advance-request
        // button — same endpoint, advances the driver's own submitted request.
        url = tourAccountsBaseUrl + 'driver_advance_payment_request';
        payload = Object.assign({}, common, {
            requestId: row.driver_advance_request_id,
            hotelAcc:  row.supplier_acc,
            hotelName: row.supplier,
            type:      'Transport Agent',
        });
    } else {
        Swal.fire('Warning', 'Payment request is not supported for this category.', 'warning');
        return;
    }

    const submitBtn = document.getElementById('pr_submit_btn');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Requesting...';
    // tf-offers-r1: the rate the authorized user picked is recorded (who, when, which) before the request goes
    if (isHotel && row.cost_offer && !(await _saveOfferPick(row))) {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="ri-send-plane-line me-1"></i> Send Request';
        return;
    }

    fetch(url, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: new URLSearchParams(payload),
    })
        .then(r => r.json())
        .then(res => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="ri-send-plane-line me-1"></i> Send Request';
            if (res.status === 200) {
                bootstrap.Modal.getInstance(document.getElementById('paymentRequestModal')).hide();
                // tf-offers-r3: held for the System Administrator's rate review — not requested yet
                Swal.fire(res.held ? 'Sent for rate review' : 'Success', res.message || 'Payment request submitted.', res.held ? 'info' : 'success').then(() => {
                    searchTour(); // Refresh dashboard
                });
            } else {
                Swal.fire('Error', res.message || 'Failed to submit payment request.', 'error');
            }
        })
        .catch(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="ri-send-plane-line me-1"></i> Send Request';
            Swal.fire('Error', 'Network error. Please try again.', 'error');
        });
}

// ==========================================
//  Submit Payment
// ==========================================
function submitPayment(isAdvance, isFullPay) {
    const tourId      = document.getElementById('pay_tour_id').value;
    const manualId    = document.getElementById('pay_manual_id').value;
    const supplierAcc = document.getElementById('pay_supplier_acc').value;
    const supplierName= document.getElementById('pay_supplier_name').value;
    const amount      = document.getElementById('pay_amount').value;
    const method      = document.getElementById('pay_method').value;
    const bankSel     = document.getElementById('pay_bank_acc');
    const bankAcc     = bankSel.value;
    const bankName    = bankSel.options[bankSel.selectedIndex]?.dataset?.name || '';
    const payDate     = document.getElementById('pay_date').value;
    const reference   = document.getElementById('pay_reference').value;
    const remarks     = document.getElementById('pay_remarks').value;
    const category    = document.getElementById('pay_category').value;
    const rowTotal    = document.getElementById('pay_row_total').value;

    if (!tourId)      { Swal.fire('Warning', 'Please search and select a tour first.', 'warning'); return; }
    if (!supplierAcc) { Swal.fire('Warning', 'No supplier selected. Click "Pay Now" on a table row.', 'warning'); return; }
    if (!amount || parseFloat(amount) <= 0) { Swal.fire('Warning', 'Please enter a valid amount.', 'warning'); return; }
    if (!bankAcc)     { Swal.fire('Warning', 'Please select a bank account.', 'warning'); return; }
    if (!payDate)     { Swal.fire('Warning', 'Please select payment date.', 'warning'); return; }

    const formData = new FormData();
    formData.append('tour_id',      tourId);
    formData.append('manual_id',    manualId);
    formData.append('supplier_acc', supplierAcc);
    formData.append('supplier_name',supplierName);
    formData.append('amount',       amount);
    formData.append('payment_method', method);
    formData.append('bank_acc',     bankAcc);
    formData.append('bank_name',    bankName);
    formData.append('payment_date', payDate);
    formData.append('reference_no', reference);
    formData.append('remarks',      remarks);
    formData.append('category',     category);
    formData.append('row_total',    rowTotal);
    formData.append('is_advance',   isAdvance ? '1' : '0');
    formData.append('is_full_pay',  isFullPay ? '1' : '0');
    formData.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');

    Swal.fire({
        title: 'Confirm Payment',
        html: `<strong>${category}</strong> payment of <strong>LKR ${parseFloat(amount).toLocaleString()}</strong><br>to <strong>${supplierName}</strong>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Save'
    }).then(result => {
        if (!result.isConfirmed) return;

        fetch('<?= base_url('tour/tour_payment_dashboard/save_payment') ?>', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(res => {
            if (res.status === 200) {
                Swal.fire('Success', res.message, 'success').then(() => {
                    clearPaymentForm();
                    searchTour(); // Refresh dashboard
                });
            } else {
                Swal.fire('Error', res.message, 'error');
            }
        })
        .catch(() => Swal.fire('Error', 'Network error. Please try again.', 'error'));
    });
}

// ==========================================
//  Toggle Make Payment Panel (side collapse)
// ==========================================
function toggleMakePaymentPanel() {
    document.getElementById('makePaymentCol').classList.toggle('tpd-collapsed');
}

// ==========================================
//  Clear Payment Form
// ==========================================
function clearPaymentForm() {
    ['pay_tour_id','pay_manual_id','pay_supplier_acc','pay_row_total',
     'pay_supplier_name','pay_amount','pay_reference','pay_remarks'].forEach(id => {
        document.getElementById(id).value = '';
    });
    document.getElementById('pay_category').value = '';
    document.getElementById('pay_method').value   = '';
    document.getElementById('pay_bank_acc').value = '';
    document.getElementById('pay_date').value     = '<?= date('Y-m-d') ?>';
    document.getElementById('pay_total_amt').textContent    = '-';
    document.getElementById('pay_paid_so_far').textContent  = '-';
    document.getElementById('pay_remaining').textContent    = '-';
}

// ==========================================
//  Helpers
// ==========================================
function fmtNum(n) {
    return parseFloat(n || 0).toLocaleString('en-US', {minimumFractionDigits: 0, maximumFractionDigits: 0});
}

// Exchange rates need decimal precision (e.g. 324.56) — fmtNum() rounds to a whole
// number, which is correct for LKR totals but silently misrepresents the rate itself.
function fmtRate(n) {
    return parseFloat(n || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

function fmtDate(d) {
    if (!d) return '-';
    const dt = new Date(d);
    return dt.toLocaleDateString('en-GB', {day:'2-digit', month:'short', year:'numeric'});
}

// Hotel rows carry every night's date (stay_dates) so a multi-night booking shows
// all dates comma-separated + a nights badge, instead of collapsing to one date.
function _stayDatesHtml(row) {
    if (row.category !== 'Hotel' || !row.stay_dates || !row.stay_dates.length) {
        return row.due_date ? fmtDate(row.due_date) : '-';
    }
    const dateList = row.stay_dates.map(fmtDate).join(', ');
    const nights   = row.night_count || row.stay_dates.length;
    const badge    = nights > 1 ? ` <span class="badge bg-light text-dark border">${nights} nights</span>` : '';
    return dateList + badge;
}

function statusClass(s) {
    const map = {
        'Fully Paid':              'bg-success',
        'Partially Paid':          'bg-warning text-dark',
        'Pending Payment':         'bg-warning text-dark',
        'Pending Approval':        'bg-purple',
        'Advance Paid':            'bg-primary',
        'Not Paid':                'bg-danger',
        'Requested':               'bg-purple',
        'Cancelled':               'bg-secondary',
        'Payment Not Requested':     'bg-secondary',
        'Waiting Rate Confirmation': 'bg-orange',
        'Payment on Process':        'bg-teal',
        'Paid':                      'bg-success',
    };
    return map[s] || 'bg-secondary';
}

function ticketStatusClass(s) {
    const map = {
        'pending':  'bg-warning text-dark',
        'approved': 'bg-success',
        'rejected': 'bg-danger',
    };
    return map[s] || 'bg-secondary';
}

function renderTickets(tickets) {
    const tbody = document.getElementById('tickets_table_body');
    tbody.innerHTML = '';

    if (!tickets.length) {
        document.getElementById('tickets_empty_msg').style.display = 'block';
        document.getElementById('tickets_table').style.display = 'none';
        return;
    }
    document.getElementById('tickets_empty_msg').style.display = 'none';
    document.getElementById('tickets_table').style.display = '';

    tickets.forEach(t => {
        const statusCls = ticketStatusClass(t.status);
        const statusLabel = t.status ? t.status.charAt(0).toUpperCase() + t.status.slice(1) : '-';
        const approvedHtml = t.status === 'rejected'
            ? `<span class="text-danger small" title="${esc(t.rejection_reason || '')}"><i class="ri-close-circle-line me-1"></i>${esc(t.approved_by || '-')}</span>`
            : (t.approved_by ? esc(t.approved_by) : '<span class="text-muted">-</span>');
        tbody.innerHTML += `<tr>
            <td class="fw-semibold text-primary">${esc(t.usage_note_no)}</td>
            <td class="small">${fmtDate(t.date)}</td>
            <td class="small">${esc(t.item_names || '-')}</td>
            <td class="text-center small">${t.total_quantity ?? 0}</td>
            <td><span class="badge rounded-pill ${statusCls}">${statusLabel}</span></td>
            <td class="small">${approvedHtml}</td>
        </tr>`;
    });
}

function statusColor(s) {
    if (s === 'Completed')   return 'bg-success text-white';
    if (s === 'Cancelled')   return 'bg-danger text-white';
    return 'bg-primary text-white';
}

function catColorClass(cat) {
    const map = {
        'Hotel':     'bg-primary',
        'Activity':  'bg-success',
        'Transport': 'bg-info text-dark',
        'Driver Advance': 'bg-info text-dark',
        'Driver':    'bg-purple',
        'Other':     'bg-warning text-dark',
    };
    return map[cat] || 'bg-secondary';
}

function esc(str) {
    const d = document.createElement('div');
    d.appendChild(document.createTextNode(str || ''));
    return d.innerHTML;
}
</script>
</body>
