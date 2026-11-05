<?= view('layouts/head') ?>

<body>
    <style>
        #printArea {
            display: none;
        }

        @media print {
            @page {
                size: A4 landscape;
                margin: 8mm 10mm;
            }

            body * {
                visibility: hidden;
            }

            #printArea, #printArea * {
                visibility: visible;
            }

            #printArea {
                display: block;
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                margin: 0;
                padding: 0;
            }
        }

        .print-title {
            text-align: center;
            font-family: Georgia, 'Times New Roman', serif;
            font-size: 16pt;
            font-weight: 700;
            letter-spacing: 1.5px;
            margin: 0 0 1mm 0;
        }

        .print-subtitle {
            text-align: center;
            font-size: 8pt;
            color: #444;
            margin: 0 0 2mm 0;
        }

        .print-grid {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 8pt;
        }

        .print-grid th,
        .print-grid td {
            border: 1.25px solid #000 !important;
            padding: 1mm 1mm;
            vertical-align: top;
            word-wrap: break-word;
        }

        .print-grid thead th {
            background-color: #e9e9e9 !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            text-align: left;
            font-size: 8pt;
            text-transform: uppercase;
        }

        .print-grid tbody tr:nth-child(even) {
            background-color: #f7f7f7 !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .print-grid .cell-line {
            display: block;
            line-height: 1.2;
        }

        .print-grid .cell-sub {
            display: block;
            font-size: 7pt;
            color: #333;
            line-height: 1.15;
        }

        .p-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #495057;
        }

        .table-hover tbody tr:hover {
            background-color: #f8f9fa;
            cursor: pointer;
        }

        #completed_tours_table tbody tr.table-danger,
        #completed_tours_table tbody tr.table-danger td,
        #ongoing_tours_table tbody tr.table-danger,
        #ongoing_tours_table tbody tr.table-danger td {
            background-color: #f8d7da !important;
        }

        #completed_tours_table tbody tr.table-danger:hover,
        #completed_tours_table tbody tr.table-danger:hover td,
        #ongoing_tours_table tbody tr.table-danger:hover,
        #ongoing_tours_table tbody tr.table-danger:hover td {
            background-color: #f1aeb5 !important;
        }

        #completed_tours_table tbody tr.table-success,
        #completed_tours_table tbody tr.table-success td {
            background-color: #d1e7dd !important;
        }

        #completed_tours_table tbody tr.table-success:hover,
        #completed_tours_table tbody tr.table-success:hover td {
            background-color: #a3cfbb !important;
        }

        #completed_tours_table tbody tr.table-warning,
        #completed_tours_table tbody tr.table-warning td {
            background-color: #fff3cd !important;
        }

        #completed_tours_table tbody tr.table-warning:hover,
        #completed_tours_table tbody tr.table-warning:hover td {
            background-color: #ffe69c !important;
        }

        .legend-swatch {
            display: inline-block;
            width: 16px;
            height: 16px;
            border-radius: 3px;
            border: 1px solid rgba(0,0,0,0.1);
        }

        .legend-swatch-danger {
            background-color: #f8d7da;
        }

        .legend-swatch-warning {
            background-color: #fff3cd;
        }

        .legend-swatch-success {
            background-color: #d1e7dd;
        }

        .completed-driver-block {
            padding: 4px 0;
        }

        .status-badge-requested {
            background-color: #ffc107;
            color: #000;
        }

        .status-badge-approved {
            background-color: #0dcaf0;
            color: #000;
        }

        .status-badge-paid {
            background-color: #78b848;
        }

        .detail-section-title {
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: #6c757d;
            border-bottom: 2px solid #dee2e6;
            padding-bottom: 6px;
            margin-bottom: 12px;
        }

        .detail-empty {
            color: #adb5bd;
            font-style: italic;
            font-size: 0.9rem;
        }

        .consignee-flight-sub {
            display: block;
            font-size: 0.76rem;
            color: #6c757d;
            line-height: 1.3;
            margin-top: 1px;
        }

        .driver-chip {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 8px 14px;
            font-weight: 600;
            font-size: 0.9rem;
            color: #212529;
        }

        #detail_advances table th,
        #detail_tickets table th {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.02em;
            color: #6c757d;
        }

        #detail_advances table td,
        #detail_advances table th,
        #detail_tickets table td,
        #detail_tickets table th {
            padding: 10px 12px;
            vertical-align: middle;
        }

        .comparison-card {
            height: 100%;
            border-width: 2px;
        }

        .comparison-card-header {
            font-weight: 700;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .comparison-card-driver {
            border-color: #0d6efd;
        }

        .comparison-card-driver .comparison-card-header {
            background-color: #cfe2ff;
            color: #084298;
        }

        .comparison-card-back-office {
            border-color: #ffc107;
        }

        .comparison-card-back-office .comparison-card-header {
            background-color: #fff3cd;
            color: #664d03;
        }
    </style>

    <!-- Fixed Header with Logo and Back Button -->
    <?= view('layouts/topbar', ['topbar_back' => base_url('dashboard2/tiles'), 'topbar_home' => false, 'topbar_logout' => false, 'topbar_class' => 'no-print']) ?>

    <!-- Main Content Container -->
    <div class="container-fluid p-4 mt-5">
        <div class="row">
            <div class="col-md-12">
                <h2 class="p-title text-center mb-4">UPCOMING TOURS</h2>
            </div>
        </div>

        <div class="row mb-3 no-print">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <label for="filter_start_date_from" class="form-label">Start Date From</label>
                                <input type="date" class="form-control" id="filter_start_date_from">
                            </div>
                            <div class="col-md-3">
                                <label for="filter_start_date_to" class="form-label">Start Date To</label>
                                <input type="date" class="form-control" id="filter_start_date_to">
                            </div>
                            <div class="col-md-3 d-flex gap-2">
                                <button class="btn btn-primary" id="filterApplyBtn">
                                    <i class="bi bi-search"></i> Apply
                                </button>
                                <button class="btn btn-secondary" id="filterClearBtn">
                                    <i class="bi bi-x-circle"></i> Clear
                                </button>
                            </div>
                            <div class="col-md-3 d-flex justify-content-end align-items-center gap-2">
                                <span class="mb-0">Tours: <span class="badge bg-success" id="upcoming_tours_count">0</span></span>
                                <button class="btn btn-outline-secondary" onclick="printReport()">
                                    <i class="bi bi-printer"></i> Print
                                </button>
                                <button class="btn btn-outline-primary" onclick="loadUpcomingTours()">
                                    <i class="bi bi-arrow-clockwise"></i> Refresh
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover" id="upcoming_tours_table">
                                <thead class="table-success">
                                    <tr>
                                        <th>Manual ID</th>
                                        <th>Tour Name</th>
                                        <th class="text-center">PAX</th>
                                        <th>Template</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th>Driver</th>
                                        <th>Required Entrance Tickets</th>
                                    </tr>
                                </thead>
                                <tbody id="upcoming_tours_tbody">
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">
                                            <i class="bi bi-hourglass-split fs-3"></i>
                                            <p class="mb-0 mt-2">Loading upcoming tours...</p>
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot id="upcoming_tours_tfoot" class="d-none"></tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-5">
            <div class="col-md-12">
                <h2 class="p-title text-center mb-4">ONGOING TOURS</h2>
            </div>
        </div>

        <div class="row mb-3 no-print">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-end align-items-center gap-2">
                            <span class="mb-0">Tours: <span class="badge bg-success" id="ongoing_tours_count">0</span></span>
                            <button class="btn btn-outline-warning" id="sendRemindersBtn" onclick="sendMeterReadingReminders()" title="SMS drivers who haven't submitted today's start meter reading">
                                <i class="bi bi-bell"></i> Send Reminders
                            </button>
                            <button class="btn btn-outline-primary" onclick="loadOngoingTours()">
                                <i class="bi bi-arrow-clockwise"></i> Refresh
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-2 no-print">
            <div class="col-md-12">
                <div class="d-flex align-items-center gap-4 flex-wrap px-2">
                    <small class="text-muted fw-bold text-uppercase">Row Status:</small>
                    <div class="d-flex align-items-center gap-2">
                        <span class="legend-swatch legend-swatch-danger"></span>
                        <small>Today's start reading not added</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover" id="ongoing_tours_table">
                                <thead class="table-warning">
                                    <tr>
                                        <th>Manual ID</th>
                                        <th>Tour Name</th>
                                        <th class="text-center">PAX</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th>Driver</th>
                                        <th>Current Mileage</th>
                                        <th>Current Invoices</th>
                                    </tr>
                                </thead>
                                <tbody id="ongoing_tours_tbody">
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">
                                            <i class="bi bi-hourglass-split fs-3"></i>
                                            <p class="mb-0 mt-2">Loading ongoing tours...</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-5">
            <div class="col-md-12">
                <h2 class="p-title text-center mb-4">COMPLETED TOURS</h2>
            </div>
        </div>

        <div class="row mb-3 no-print">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <label for="completed_start_date_from" class="form-label">Start Date From</label>
                                <input type="date" class="form-control" id="completed_start_date_from">
                            </div>
                            <div class="col-md-3">
                                <label for="completed_start_date_to" class="form-label">Start Date To</label>
                                <input type="date" class="form-control" id="completed_start_date_to">
                            </div>
                            <div class="col-md-3 d-flex gap-2">
                                <button class="btn btn-primary" id="completedFilterApplyBtn">
                                    <i class="bi bi-search"></i> Apply
                                </button>
                                <button class="btn btn-secondary" id="completedFilterClearBtn">
                                    <i class="bi bi-x-circle"></i> Clear
                                </button>
                            </div>
                            <div class="col-md-3 d-flex justify-content-end align-items-center gap-2">
                                <span class="mb-0">Tours: <span class="badge bg-success" id="completed_tours_count">0</span></span>
                                <span class="mb-0">Settlements Pending: <span class="badge bg-danger" id="pending_settlement_count">0</span></span>
                                <button class="btn btn-outline-primary" onclick="loadCompletedTours()">
                                    <i class="bi bi-arrow-clockwise"></i> Refresh
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-2 no-print">
            <div class="col-md-12">
                <div class="d-flex align-items-center gap-4 flex-wrap px-2">
                    <small class="text-muted fw-bold text-uppercase">Row Status:</small>
                    <div class="d-flex align-items-center gap-2">
                        <span class="legend-swatch legend-swatch-danger"></span>
                        <small>No mileage added</small>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="legend-swatch legend-swatch-warning"></span>
                        <small>Mileage added, settlement not paid</small>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="legend-swatch legend-swatch-success"></span>
                        <small>Settlement paid</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover" id="completed_tours_table">
                                <thead class="table-secondary">
                                    <tr>
                                        <th>Manual ID</th>
                                        <th>Tour Name</th>
                                        <th class="text-center">PAX</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th>Driver</th>
                                        <th>Back Office</th>
                                    </tr>
                                </thead>
                                <tbody id="completed_tours_tbody">
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">
                                            <i class="bi bi-hourglass-split fs-3"></i>
                                            <p class="mb-0 mt-2">Loading completed tours...</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Print Template (populated at print time, hidden on screen) -->
    <div id="printArea">
        <div class="print-title">UPCOMING TOURS</div>
        <div class="print-subtitle" id="print_subtitle"></div>
        <table class="print-grid">
            <thead>
                <tr>
                    <th style="width:8%">Manual ID</th>
                    <th style="width:16%">Tour Name</th>
                    <th style="width:5%">PAX</th>
                    <th style="width:10%">Template</th>
                    <th style="width:9%">Start Date</th>
                    <th style="width:9%">End Date</th>
                    <th style="width:23%">Driver</th>
                    <th style="width:20%">Required Entrance Tickets</th>
                </tr>
            </thead>
            <tbody id="print_tbody"></tbody>
        </table>
    </div>

    <!-- Tour Detail Modal -->
    <div class="modal fade" id="tourDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable" style="max-width: 95vw;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="tourDetailModalLabel">Tour Details</h5>
                    <div class="d-flex align-items-center gap-2 ms-auto me-3">
                        <select class="form-select form-select-sm no-print" id="actionDriverSelect" style="display:none; width:auto;" title="Driver to act on behalf of"></select>
                        <span class="badge bg-success" id="finalizedBadge" style="display:none;">
                            <i class="bi bi-check-circle-fill me-1"></i> Finalized
                        </span>
                        <button type="button" class="btn btn-sm btn-success no-print" id="finalizeTourBtn" style="display:none;">
                            <i class="bi bi-flag-fill me-1"></i> Finalize Tour
                        </button>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="detail-section-title">Tour</div>
                            <div class="fw-semibold" id="detail_tour_name"></div>
                            <small class="text-muted" id="detail_tour_dates"></small>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-section-title">PAX / Template</div>
                            <div class="fw-semibold" id="detail_pax"></div>
                            <small class="text-muted" id="detail_template"></small>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="detail-section-title">Drivers</div>
                        <div id="detail_drivers"></div>
                    </div>

                    <div class="mb-4">
                        <div class="detail-section-title">Driver Advances</div>
                        <div class="table-responsive" id="detail_advances"></div>
                    </div>

                    <div class="mb-4" id="detail_tickets_section">
                        <div class="detail-section-title">Required Entrance Tickets</div>
                        <div class="table-responsive" id="detail_tickets"></div>
                    </div>

                    <div class="row" id="detail_comparison_section">
                        <div class="col-md-6" id="detail_invoices_section">
                            <div class="card comparison-card comparison-card-driver">
                                <div class="card-header comparison-card-header">
                                    <i class="bi bi-person-fill me-1"></i> Driver Log Sheet Entries
                                </div>
                                <div class="card-body">
                                    <div class="detail-section-title d-flex justify-content-between align-items-center" id="detail_mileage_section">
                                        <span>Mileage</span>
                                        <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2 no-print" id="addMileageBtn" style="display:none;" title="Upload meter reading on behalf of driver">
                                            <i class="bi bi-plus-lg"></i> Add
                                        </button>
                                    </div>
                                    <div id="addMileageForm" class="border rounded p-2 mb-2" style="display:none;"></div>
                                    <div class="table-responsive" id="detail_mileage"></div>

                                    <div class="detail-section-title mt-3 d-flex justify-content-between align-items-center">
                                        <span>Expenses</span>
                                        <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2 no-print" id="addExpenseBtn" style="display:none;" title="Upload invoice on behalf of driver">
                                            <i class="bi bi-plus-lg"></i> Add
                                        </button>
                                    </div>
                                    <div id="addExpenseForm" class="border rounded p-2 mb-2" style="display:none;"></div>
                                    <div class="table-responsive" id="detail_invoices"></div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6" id="detail_back_office_section">
                            <div class="card comparison-card comparison-card-back-office">
                                <div class="card-header comparison-card-header">
                                    <i class="bi bi-building me-1"></i> Back Office
                                </div>
                                <div class="card-body">
                                    <div class="detail-section-title">Log Sheet Mileage</div>
                                    <div class="table-responsive" id="detail_back_office_mileage"></div>

                                    <div class="detail-section-title mt-3">Expenses</div>
                                    <div class="table-responsive" id="detail_back_office"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery CDN -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- DataTables CDN -->
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap4.min.js"></script>
    <!-- Bootstrap Bundle (modal support) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        let toursData = [];
        let ticketStockData = {};
        let dataTable;
        let ongoingToursData = [];
        let ongoingDataTable;
        let completedToursData = [];
        let completedDataTable;

        function escapeHtml(text) {
            if (text === null || text === undefined) return '';
            return $('<div>').text(text).html();
        }

        function formatDate(dateStr) {
            if (!dateStr) return '-';
            const d = new Date(dateStr);
            if (isNaN(d.getTime())) return dateStr;
            return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
        }

        function formatMoney(amount) {
            const n = parseFloat(amount);
            if (isNaN(n)) return '-';
            return n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function statusBadge(status) {
            const labels = { requested: 'Advance Requested', approved: 'Advance Approved', paid: 'Advance Paid' };
            const label = escapeHtml(labels[status] || status);
            const cssClass = ['requested', 'approved', 'paid'].includes(status) ? `status-badge-${status}` : 'status-badge-requested';
            return `<span class="badge ${cssClass}">${label}</span>`;
        }

        function backOfficeStatusBadge(status) {
            const labels = { pending: 'Pending', approved: 'Approved', rejected: 'Rejected' };
            const cssClasses = { pending: 'status-badge-requested', approved: 'status-badge-paid', rejected: 'bg-secondary' };
            const label = escapeHtml(labels[status] || status);
            const cssClass = cssClasses[status] || 'status-badge-requested';
            return `<span class="badge ${cssClass}">${label}</span>`;
        }

        /* Vehicle display hidden for now (supplier.v_num/type unpopulated live) — re-enable by swapping
           the driver rendering in showTourDetail() back to driverWithVehicleHtml(d).
        function vehicleLinesHtml(driver) {
            const vehicles = driver.vehicles || [];
            if (vehicles.length === 0) {
                return '<small class="detail-empty">No vehicle registered</small>';
            }
            return vehicles.map(v =>
                `<small class="d-block">${escapeHtml(v.type || 'Vehicle')} &mdash; ${escapeHtml(v.v_num || 'No reg. no')}</small>`
            ).join('');
        }

        function driverWithVehicleHtml(d) {
            const name = escapeHtml(d.driver_name || d.driver);
            return `
                <div class="d-inline-block align-top border rounded px-2 py-1 me-2 mb-2">
                    <div class="fw-semibold">${name}</div>
                    ${vehicleLinesHtml(d)}
                </div>
            `;
        }
        */

        function driverPhoneLine(d) {
            if (!d.driver_mobile) return '';
            return `<small class="text-muted d-block"><i class="bi bi-telephone"></i> ${escapeHtml(d.driver_mobile)}</small>`;
        }

        function driverVehicleLines(d) {
            const vehicles = d.vehicles || [];
            if (vehicles.length === 0) return '';
            return vehicles.map(v =>
                `<small class="text-muted d-block"><i class="bi bi-truck"></i> ${escapeHtml(v.type || 'Vehicle')}${v.v_num ? ' — ' + escapeHtml(v.v_num) : ''}</small>`
            ).join('');
        }

        function driverSubtext(d) {
            return driverVehicleLines(d) + driverPhoneLine(d);
        }

        function driverCellHtml(tour) {
            const drivers = tour.drivers || [];

            if (drivers.length === 0) {
                return '<span class="detail-empty">Unassigned</span>';
            }

            return drivers.map(d => {
                const advance = (tour.driver_advances || []).find(a => a.driver === d.driver);
                const name = escapeHtml(d.driver_name || d.driver);

                if (!advance) {
                    return `<div class="mb-1"><div>${name}</div>${driverSubtext(d)}<small class="detail-empty">No advance</small></div>`;
                }

                return `
                    <div class="mb-1">
                        <div>${name}</div>
                        ${driverSubtext(d)}
                        <small>${statusBadge(advance.status)} ${formatMoney(advance.requested_amount)}</small>
                    </div>
                `;
            }).join('');
        }

        function usageNoteBadge(status) {
            const labels = { pending: 'Usage Note: Pending', approved: 'Usage Note: Approved', rejected: 'Usage Note: Rejected' };
            const classes = { pending: 'bg-warning text-dark', approved: 'bg-success', rejected: 'bg-danger' };
            const label = labels[status] || ('Usage Note: ' + escapeHtml(status));
            const cssClass = classes[status] || 'bg-secondary';
            return `<span class="badge ${cssClass}">${label}</span>`;
        }

        function createUsageNoteBtn(tour) {
            const url = '<?= base_url('inventory/usage_note/create') ?>'
                + '?tour_id=' + encodeURIComponent(tour.tour_id)
                + '&manual_id=' + encodeURIComponent(tour.manual_id || '');
            return `<a href="${url}" target="_blank" class="btn btn-outline-primary btn-sm py-0 px-2" title="Create Usage Note" onclick="event.stopPropagation()">
                <i class="bi bi-plus-lg"></i> Usage Note
            </a>`;
        }

        function ticketsCellHtml(tour) {
            const tickets = tour.entrance_fees || [];

            if (tickets.length === 0) {
                return '<span class="detail-empty">None</span>';
            }

            const pax = parseInt(tour.passenger_count, 10) || 0;

            const lines = tickets.map(t => {
                const place = t.place_name || 'Unknown';
                const statusPart = t.usage_note
                    ? usageNoteBadge(t.usage_note.status)
                    : createUsageNoteBtn(tour);

                return `
                    <div class="d-flex justify-content-between align-items-center gap-2 mb-1">
                        <small>${pax} &times; ${escapeHtml(place)}</small>
                        ${statusPart}
                    </div>
                `;
            }).join('');

            return lines;
        }

        function renderUpcomingTicketsFooter() {
            const $tfoot = $('#upcoming_tours_tfoot');

            if (toursData.length === 0) {
                $tfoot.addClass('d-none').html('');
                return;
            }

            // Per ticket type, across all currently loaded/filtered tours:
            //   required  = every ticket line (pax count), regardless of status
            //   submitted = ticket lines that already have a usage note (pending or approved)
            const summary = {
                '0001': { required: 0, submitted: 0 },
                '0002': { required: 0, submitted: 0 },
            };
            toursData.forEach(tour => {
                const pax = parseInt(tour.passenger_count, 10) || 0;
                (tour.entrance_fees || []).forEach(t => {
                    if (!t.item_code || !summary[t.item_code]) return;
                    summary[t.item_code].required += pax;
                    if (t.usage_note) {
                        summary[t.item_code].submitted += pax;
                    }
                });
            });

            const rateFor = code => (ticketStockData && ticketStockData[code]) ? ticketStockData[code] : { available_qty: 0, current_rate: 0, description: code };

            const rows = Object.keys(summary).map(code => {
                const stock = rateFor(code);
                const required = summary[code].required;
                const submitted = summary[code].submitted;
                const currentStock = stock.available_qty || 0;
                const needToOrder = Math.max(0, required - currentStock);

                return `
                    <tr>
                        <td>${escapeHtml(stock.description || code)}</td>
                        <td class="text-center">${required}</td>
                        <td class="text-center">${submitted}</td>
                        <td class="text-center">${currentStock}</td>
                        <td class="text-center fw-bold ${needToOrder > 0 ? 'text-danger' : 'text-success'}">${needToOrder}</td>
                    </tr>
                `;
            }).join('');

            $tfoot.removeClass('d-none').html(`
                <tr>
                    <td colspan="8" class="p-0 border-0">
                        <div class="bg-light p-3">
                            <div class="fw-bold mb-2">Required Entrance Tickets Summary</div>
                            <table class="table table-sm table-bordered mb-0 bg-white">
                                <thead class="table-light">
                                    <tr>
                                        <th>Ticket</th>
                                        <th class="text-center">Required</th>
                                        <th class="text-center">Already Submitted</th>
                                        <th class="text-center">Current Stock</th>
                                        <th class="text-center">Need to Order</th>
                                    </tr>
                                </thead>
                                <tbody>${rows}</tbody>
                            </table>
                        </div>
                    </td>
                </tr>
            `);
        }

        function loadUpcomingTours() {
            $('#upcoming_tours_tbody').html(`
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">
                        <i class="bi bi-hourglass-split fs-3"></i>
                        <p class="mb-0 mt-2">Loading upcoming tours...</p>
                    </td>
                </tr>
            `);

            const params = {};
            const from = $('#filter_start_date_from').val();
            const to = $('#filter_start_date_to').val();
            if (from) params.start_date_from = from;
            if (to) params.start_date_to = to;

            $.ajax({
                url: '<?= base_url('dashboard2/transport/transport_portal/upcoming_tours') ?>',
                type: 'GET',
                data: params,
                dataType: 'json',
                success: function(response) {
                    toursData = (response && response.data) ? response.data : [];
                    ticketStockData = (response && response.ticket_stock) ? response.ticket_stock : {};
                    renderTable();
                },
                error: function() {
                    $('#upcoming_tours_tbody').html(`
                        <tr>
                            <td colspan="8" class="text-center text-danger py-4">
                                <i class="bi bi-exclamation-triangle fs-3"></i>
                                <p class="mb-0 mt-2">Failed to load upcoming tours.</p>
                            </td>
                        </tr>
                    `);
                }
            });
        }

        function printDriverLines(tour) {
            const drivers = tour.drivers || [];
            if (drivers.length === 0) {
                return '<span class="cell-line">Unassigned</span>';
            }

            return drivers.map(d => {
                const advance = (tour.driver_advances || []).find(a => a.driver === d.driver);
                const name = escapeHtml(d.driver_name || d.driver);
                const statusLabels = { requested: 'Advance Requested', approved: 'Advance Approved', paid: 'Advance Paid' };

                if (!advance) {
                    return `<span class="cell-line">${name}<span class="cell-sub">No advance</span></span>`;
                }

                const statusLabel = escapeHtml(statusLabels[advance.status] || advance.status);
                return `<span class="cell-line">${name}<span class="cell-sub">${statusLabel} – ${formatMoney(advance.requested_amount)}</span></span>`;
            }).join('');
        }

        function printTicketLines(tour) {
            const tickets = tour.entrance_fees || [];
            if (tickets.length === 0) {
                return '<span class="cell-line">None</span>';
            }

            const pax = parseInt(tour.passenger_count, 10) || 0;
            const places = [...new Set(tickets.map(t => t.place_name || 'Unknown'))];

            return places.map(place => {
                const unitPrice = parseFloat(tickets.find(t => (t.place_name || 'Unknown') === place)?.unit_price) || 0;
                const total = pax * unitPrice;
                return `<span class="cell-line">${pax} &times; ${escapeHtml(place)} = ${formatMoney(total)}</span>`;
            }).join('');
        }

        function buildPrintArea() {
            const fromLabel = formatDate($('#filter_start_date_from').val());
            const toLabel = formatDate($('#filter_start_date_to').val());
            $('#print_subtitle').text('Confirmed tours — start date ' + fromLabel + ' to ' + toLabel);

            const rows = toursData.map(tour => {
                const templateText = tour.template_name
                    ? escapeHtml(tour.template_name)
                    : (tour.is_template == 1 ? 'Template' : '-');

                return `
                    <tr>
                        <td><strong>${escapeHtml(tour.manual_id)}</strong></td>
                        <td>${escapeHtml(tour.consignee)}</td>
                        <td>${escapeHtml(tour.passenger_count)}</td>
                        <td>${templateText}</td>
                        <td>${formatDate(tour.start_date)}</td>
                        <td>${formatDate(tour.end_date)}</td>
                        <td>${printDriverLines(tour)}</td>
                        <td>${printTicketLines(tour)}</td>
                    </tr>
                `;
            }).join('');

            $('#print_tbody').html(rows || '<tr><td colspan="8" style="text-align:center;">No upcoming tours found</td></tr>');
        }

        function printReport() {
            buildPrintArea();
            window.print();
        }

        // Small subtext line under the consignee name showing arrival/departure
        // flight + time when set on the tour, matching the "CODE - TIME"
        // convention already used elsewhere (reservation_card_print.php).
        // Omitted entirely when neither is available.
        function flightSubHtml(tour) {
            const lines = [];
            if (tour.arrival_flight) {
                lines.push(`<i class="bi bi-airplane-engines"></i> ${escapeHtml(tour.arrival_flight)}${tour.arrival_flight_time ? ' - ' + escapeHtml(tour.arrival_flight_time) : ''}`);
            }
            if (tour.departure_flight) {
                lines.push(`<i class="bi bi-airplane"></i> ${escapeHtml(tour.departure_flight)}${tour.departure_flight_time ? ' - ' + escapeHtml(tour.departure_flight_time) : ''}`);
            }
            if (!lines.length) {
                return '';
            }
            return `<span class="consignee-flight-sub">${lines.join(' &nbsp;|&nbsp; ')}</span>`;
        }

        function renderTable() {
            if (dataTable) {
                dataTable.destroy();
                dataTable = null;
            }

            $('#upcoming_tours_count').text(toursData.length);
            renderUpcomingTicketsFooter();

            if (toursData.length === 0) {
                $('#upcoming_tours_tbody').html(`
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            <i class="bi bi-info-circle fs-3"></i>
                            <p class="mb-0 mt-2">No upcoming tours found</p>
                        </td>
                    </tr>
                `);
                return;
            }

            const rows = toursData.map((tour, index) => {
                const templateLabel = tour.template_name
                    ? `<span class="badge bg-info text-dark">${escapeHtml(tour.template_name)}</span>`
                    : (tour.is_template == 1 ? '<span class="badge bg-secondary">Template</span>' : '<span class="detail-empty">-</span>');

                return `
                    <tr data-index="${index}" class="upcoming-tour-row">
                        <td><strong>${escapeHtml(tour.manual_id)}</strong></td>
                        <td>${escapeHtml(tour.consignee)}${flightSubHtml(tour)}</td>
                        <td class="text-center"><span class="badge bg-secondary">${escapeHtml(tour.passenger_count)}</span></td>
                        <td>${templateLabel}</td>
                        <td data-order="${escapeHtml(tour.start_date || '')}">${formatDate(tour.start_date)}</td>
                        <td data-order="${escapeHtml(tour.end_date || '')}">${formatDate(tour.end_date)}</td>
                        <td>${driverCellHtml(tour)}</td>
                        <td>${ticketsCellHtml(tour)}</td>
                    </tr>
                `;
            }).join('');

            $('#upcoming_tours_tbody').html(rows);

            dataTable = $('#upcoming_tours_table').DataTable({
                order: [[4, 'asc']],
                pageLength: 10,
                language: {
                    emptyTable: 'No upcoming tours found',
                    search: 'Search all columns:',
                    searchPlaceholder: 'Manual ID, name, driver...'
                }
            });
        }

        function mileageCellHtml(tour) {
            const total = parseFloat(tour.total_distance) || 0;
            if (!tour.daily_mileage || tour.daily_mileage.length === 0) {
                return '<span class="detail-empty">No readings yet</span>';
            }
            return `<span>${total.toLocaleString('en-US', { maximumFractionDigits: 0 })} km logged</span>`;
        }

        function invoicesCellHtml(tour) {
            const count = tour.expense_count || 0;
            if (count === 0) {
                return '<span class="detail-empty">No invoices yet</span>';
            }
            return `<span>${count} invoice(s) &mdash; LKR ${formatMoney(tour.total_lkr)}</span>`;
        }

        function sendMeterReadingReminders() {
            if (!confirm("Send an SMS reminder to every driver on an ongoing tour who hasn't submitted today's start meter reading?")) {
                return;
            }

            const $btn = $('#sendRemindersBtn');
            $btn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Sending...');

            $.ajax({
                url: '<?= base_url('dashboard2/transport/transport_portal/send_reminders') ?>',
                type: 'POST',
                dataType: 'json',
                success: function(response) {
                    if (!response || response.status !== 200) {
                        alert((response && response.message) || 'Failed to send reminders.');
                        return;
                    }

                    const names = (response.sent || []).map(s => `${s.driver_name} (${s.manual_id})`).join('\n');
                    const summary = `Sent ${response.sent_count} reminder(s).` +
                        (response.skipped_count ? ` Skipped ${response.skipped_count} (already sent today or no mobile on file).` : '') +
                        (names ? `\n\n${names}` : '');
                    alert(summary);

                    loadOngoingTours();
                },
                error: function() {
                    alert('Something went wrong while sending reminders.');
                },
                complete: function() {
                    $btn.prop('disabled', false).html('<i class="bi bi-bell"></i> Send Reminders');
                }
            });
        }

        $(document).on('click', '.send-driver-reminder-btn', function() {
            const $btn = $(this);
            const tourId = $btn.data('tour');
            const driverAcc = $btn.data('driver');
            const driverName = $btn.data('driverName');

            if (!confirm(`Send a meter reading reminder SMS to ${driverName}?`)) {
                return;
            }

            $btn.prop('disabled', true).find('i').removeClass('bi-bell').addClass('bi-hourglass-split');

            $.ajax({
                url: '<?= base_url('dashboard2/transport/transport_portal/send_reminder_to_driver') ?>',
                type: 'POST',
                dataType: 'json',
                data: { tour_id: tourId, driver_acc: driverAcc },
                success: function(response) {
                    alert((response && response.message) || 'Something went wrong.');
                    if (response && response.status === 200) {
                        loadOngoingTours();
                    }
                },
                error: function() {
                    alert('Something went wrong while sending the reminder.');
                },
                complete: function() {
                    $btn.prop('disabled', false).find('i').removeClass('bi-hourglass-split').addClass('bi-bell');
                }
            });
        });

        function loadOngoingTours() {
            $('#ongoing_tours_tbody').html(`
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">
                        <i class="bi bi-hourglass-split fs-3"></i>
                        <p class="mb-0 mt-2">Loading ongoing tours...</p>
                    </td>
                </tr>
            `);

            $.ajax({
                url: '<?= base_url('dashboard2/transport/transport_portal/ongoing_tours') ?>',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    ongoingToursData = (response && response.data) ? response.data : [];
                    renderOngoingTable();
                },
                error: function() {
                    $('#ongoing_tours_tbody').html(`
                        <tr>
                            <td colspan="8" class="text-center text-danger py-4">
                                <i class="bi bi-exclamation-triangle fs-3"></i>
                                <p class="mb-0 mt-2">Failed to load ongoing tours.</p>
                            </td>
                        </tr>
                    `);
                }
            });
        }

        function driverReminderBtnHtml(tour, d) {
            const status = (tour.meter_reading_status_by_driver || {})[d.driver];
            if (!status || !status.missing_today_start) {
                return '';
            }
            return `<button type="button" class="btn btn-outline-warning btn-sm py-0 px-1 ms-1 send-driver-reminder-btn"
                data-tour="${escapeHtml(tour.tour_id)}" data-driver="${escapeHtml(d.driver)}" data-driver-name="${escapeHtml(d.driver_name || d.driver)}"
                title="Send meter reading reminder SMS to ${escapeHtml(d.driver_name || d.driver)}" onclick="event.stopPropagation()">
                <i class="bi bi-bell"></i>
            </button>`;
        }

        function ongoingDriverCellHtml(tour) {
            const drivers = tour.drivers || [];

            if (drivers.length === 0) {
                return '<span class="detail-empty">Unassigned</span>';
            }

            return drivers.map(d => {
                const name = escapeHtml(d.driver_name || d.driver);
                return `
                    <div class="mb-1">
                        <div>${name}${driverReminderBtnHtml(tour, d)}</div>
                        ${driverSubtext(d)}
                    </div>
                `;
            }).join('');
        }

        function missingTodayStartReading(tour) {
            const statusByDriver = tour.meter_reading_status_by_driver || {};
            return Object.keys(statusByDriver).some(driverAcc => statusByDriver[driverAcc].missing_today_start);
        }

        function renderOngoingTable() {
            if (ongoingDataTable) {
                ongoingDataTable.destroy();
                ongoingDataTable = null;
            }

            $('#ongoing_tours_count').text(ongoingToursData.length);

            if (ongoingToursData.length === 0) {
                $('#ongoing_tours_tbody').html(`
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            <i class="bi bi-info-circle fs-3"></i>
                            <p class="mb-0 mt-2">No ongoing tours found</p>
                        </td>
                    </tr>
                `);
                return;
            }

            const rows = ongoingToursData.map((tour, index) => {
                const rowClass = missingTodayStartReading(tour) ? 'table-danger' : '';
                return `
                    <tr data-index="${index}" class="ongoing-tour-row ${rowClass}">
                        <td><strong>${escapeHtml(tour.manual_id)}</strong></td>
                        <td>${escapeHtml(tour.consignee)}</td>
                        <td class="text-center"><span class="badge bg-secondary">${escapeHtml(tour.passenger_count)}</span></td>
                        <td data-order="${escapeHtml(tour.start_date || '')}">${formatDate(tour.start_date)}</td>
                        <td data-order="${escapeHtml(tour.end_date || '')}">${formatDate(tour.end_date)}</td>
                        <td>${ongoingDriverCellHtml(tour)}</td>
                        <td>${mileageCellHtml(tour)}</td>
                        <td>${invoicesCellHtml(tour)}</td>
                    </tr>
                `;
            }).join('');

            $('#ongoing_tours_tbody').html(rows);

            ongoingDataTable = $('#ongoing_tours_table').DataTable({
                order: [[3, 'asc']],
                pageLength: 10,
                language: {
                    emptyTable: 'No ongoing tours found',
                    search: 'Search all columns:',
                    searchPlaceholder: 'Manual ID, name, driver...'
                }
            });
        }

        function finalMileageCellHtml(tour) {
            if (!tour.is_finalized) {
                // Not finalized yet — fall back to the driver-submitted running total
                // (same daily_mileage data Ongoing Tours shows) instead of hiding it.
                const provisional = parseFloat(tour.total_distance) || 0;
                if (!tour.daily_mileage || tour.daily_mileage.length === 0) {
                    return '<span class="detail-empty">No readings yet</span>';
                }
                return `<span>${provisional.toLocaleString('en-US', { maximumFractionDigits: 0 })} km <small class="text-muted">(provisional)</small></span>`;
            }
            const total = parseFloat(tour.final_distance) || 0;
            return `<span>${total.toLocaleString('en-US', { maximumFractionDigits: 0 })} km</span>`;
        }

        function finalInvoicesCellHtml(tour) {
            if (!tour.is_finalized) {
                // Not finalized yet — fall back to the driver-submitted expenses total.
                const count = tour.expense_count || 0;
                if (count === 0) {
                    return '<span class="detail-empty">No invoices yet</span>';
                }
                return `<span>${count} invoice(s) &mdash; LKR ${formatMoney(tour.total_lkr)}</span>`;
            }
            const count = (tour.finalize_records || []).reduce((sum, r) => sum + (parseInt(r.expense_count, 10) || 0), 0);
            return `<span>${count} invoice(s) &mdash; LKR ${formatMoney(tour.final_lkr)}</span>`;
        }

        function logSheetStatusHtml(tour) {
            if (tour.is_finalized) {
                return '<span class="badge bg-success">Finalized</span>';
            }
            return '<span class="badge bg-danger">Not Finalized</span>';
        }

        function completedDriverCellHtml(tour) {
            const drivers = tour.drivers || [];

            if (drivers.length === 0) {
                return '<span class="detail-empty">Unassigned</span>';
            }

            return drivers.map(d => {
                const name = escapeHtml(d.driver_name || d.driver);

                return `
                    <div class="completed-driver-block mb-2">
                        <div class="fw-bold">${name}</div>
                        ${driverSubtext(d)}
                        <div><small><strong>Total Mileage:</strong> ${finalMileageCellHtml(tour)}</small></div>
                        <div class="mt-1"><small><strong>Log Sheet Status:</strong></small> ${logSheetStatusHtml(tour)}</div>
                    </div>
                `;
            }).join('');
        }

        function completedRowStatusClass(tour) {
            // Red: no mileage submitted yet.
            const hasMileage = (tour.daily_mileage && tour.daily_mileage.length > 0) && (parseFloat(tour.total_distance) || 0) > 0;
            if (!hasMileage) {
                return 'table-danger';
            }

            // Green: driver settlement paid.
            const isSettled = tour.transport_settled == 1;
            if (isSettled) {
                return 'table-success';
            }

            // Yellow: mileage added but not yet settled (covers both expenses-approved and
            // expenses-still-pending — simplified to one state per spec).
            return 'table-warning';
        }

        function driverSettledBadge(tour) {
            // Same logic as tile_dashboard.php's recent_bookings "Settled" column:
            // consignee.transport_settled == 1 -> settled, else not settled.
            const isSettled = tour.transport_settled == 1;
            const cssClass = isSettled ? 'btn-success' : 'btn-danger';
            const icon = isSettled ? '✓' : '✗';
            return `<button type="button" class="btn ${cssClass} btn-sm" style="min-width: 40px;" disabled>${icon}</button>`;
        }

        function backOfficeCellHtml(tour) {
            const count = tour.back_office_count || 0;
            const mileage = parseFloat(tour.back_office_total_mileage) || 0;

            if (count === 0 && mileage === 0) {
                return `
                    <div>
                        <span class="detail-empty">No back office data</span>
                        <div class="mt-1"><small><strong>Settled:</strong></small> ${driverSettledBadge(tour)}</div>
                    </div>
                `;
            }

            const pending = tour.back_office_pending_count || 0;
            const approved = tour.back_office_approved_count || 0;

            return `
                <div>
                    <div><small><strong>Mileage:</strong> ${mileage.toLocaleString('en-US', { maximumFractionDigits: 0 })} km</small></div>
                    <div class="d-flex align-items-center gap-2 flex-wrap mt-1">
                        <small>${count} expense(s) &mdash; LKR ${formatMoney(tour.back_office_total_lkr)}</small>
                        ${pending > 0 ? `<span class="badge status-badge-requested">${pending} Pending</span>` : ''}
                        ${approved > 0 ? `<span class="badge status-badge-paid">${approved} Approved</span>` : ''}
                    </div>
                    <div class="mt-1"><small><strong>Settled:</strong></small> ${driverSettledBadge(tour)}</div>
                </div>
            `;
        }

        function loadPendingSettlementCount() {
            const params = {};
            const from = $('#completed_start_date_from').val();
            const to = $('#completed_start_date_to').val();
            if (from) params.start_date_from = from;
            if (to) params.start_date_to = to;

            $.ajax({
                url: '<?= base_url('dashboard2/transport/transport_portal/pending_settlement_count') ?>',
                type: 'GET',
                data: params,
                dataType: 'json',
                success: function(response) {
                    $('#pending_settlement_count').text((response && response.count) || 0);
                }
            });
        }

        function loadCompletedTours() {
            $('#completed_tours_tbody').html(`
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">
                        <i class="bi bi-hourglass-split fs-3"></i>
                        <p class="mb-0 mt-2">Loading completed tours...</p>
                    </td>
                </tr>
            `);

            loadPendingSettlementCount();

            const params = {};
            const from = $('#completed_start_date_from').val();
            const to = $('#completed_start_date_to').val();
            if (from) params.start_date_from = from;
            if (to) params.start_date_to = to;

            $.ajax({
                url: '<?= base_url('dashboard2/transport/transport_portal/completed_tours') ?>',
                type: 'GET',
                data: params,
                dataType: 'json',
                success: function(response) {
                    completedToursData = (response && response.data) ? response.data : [];
                    renderCompletedTable();
                },
                error: function() {
                    $('#completed_tours_tbody').html(`
                        <tr>
                            <td colspan="7" class="text-center text-danger py-4">
                                <i class="bi bi-exclamation-triangle fs-3"></i>
                                <p class="mb-0 mt-2">Failed to load completed tours.</p>
                            </td>
                        </tr>
                    `);
                }
            });
        }

        function renderCompletedTable() {
            if (completedDataTable) {
                completedDataTable.destroy();
                completedDataTable = null;
            }

            $('#completed_tours_count').text(completedToursData.length);

            if (completedToursData.length === 0) {
                $('#completed_tours_tbody').html(`
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="bi bi-info-circle fs-3"></i>
                            <p class="mb-0 mt-2">No completed tours found</p>
                        </td>
                    </tr>
                `);
                return;
            }

            const rows = completedToursData.map((tour, index) => {
                const rowClass = completedRowStatusClass(tour);
                return `
                    <tr data-index="${index}" class="completed-tour-row ${rowClass}">
                        <td><strong>${escapeHtml(tour.manual_id)}</strong></td>
                        <td>${escapeHtml(tour.consignee)}</td>
                        <td class="text-center"><span class="badge bg-secondary">${escapeHtml(tour.passenger_count)}</span></td>
                        <td data-order="${escapeHtml(tour.start_date || '')}">${formatDate(tour.start_date)}</td>
                        <td data-order="${escapeHtml(tour.end_date || '')}">${formatDate(tour.end_date)}</td>
                        <td>${completedDriverCellHtml(tour)}</td>
                        <td>${backOfficeCellHtml(tour)}</td>
                    </tr>
                `;
            }).join('');

            $('#completed_tours_tbody').html(rows);

            completedDataTable = $('#completed_tours_table').DataTable({
                order: [[4, 'desc']],
                pageLength: 10,
                language: {
                    emptyTable: 'No completed tours found',
                    search: 'Search all columns:',
                    searchPlaceholder: 'Manual ID, name, driver...'
                }
            });
        }

        let currentDetailTour = null;
        let currentDetailSource = null;

        function showTourDetail(index, source) {
            const dataSource = source === 'ongoing' ? ongoingToursData : (source === 'completed' ? completedToursData : toursData);
            const tour = dataSource[index];
            if (!tour) return;

            currentDetailTour = tour;
            currentDetailSource = source;
            $('#addMileageForm, #addExpenseForm').hide().empty();

            const showsFinalizeControls = source === 'ongoing' || source === 'completed';
            populateActionDriverSelect(tour, showsFinalizeControls);
            refreshDriverActionState();

            $('#detail_tickets_section').toggle(source !== 'ongoing' && source !== 'completed');
            $('#detail_invoices_section').toggle(source === 'ongoing' || source === 'completed');
            $('#detail_back_office_section').toggle(source === 'completed');
            $('#detail_invoices_section').toggleClass('col-md-6', source === 'completed').toggleClass('col-md-12', source !== 'completed');

            $('#tourDetailModalLabel').text(tour.manual_id + ' - ' + tour.consignee);
            $('#detail_tour_name').text(tour.consignee);
            $('#detail_tour_dates').text(formatDate(tour.start_date) + ' → ' + formatDate(tour.end_date));
            $('#detail_pax').text(tour.passenger_count + ' passenger(s)');
            $('#detail_template').text(tour.template_name ? ('Template: ' + tour.template_name) : (tour.is_template == 1 ? 'This tour is a template' : 'No template'));

            // Drivers
            if (!tour.drivers || tour.drivers.length === 0) {
                $('#detail_drivers').html('<span class="detail-empty">No drivers allocated</span>');
            } else {
                // Vehicle info hidden for now (supplier.v_num/type unpopulated live) — see driverWithVehicleHtml()
                $('#detail_drivers').html(`<div class="d-flex flex-wrap gap-2">${tour.drivers.map(d =>
                    `<span class="driver-chip">${escapeHtml(d.driver_name || d.driver)}</span>`
                ).join('')}</div>`);
            }

            // Advances
            if (!tour.driver_advances || tour.driver_advances.length === 0) {
                $('#detail_advances').html('<span class="detail-empty">No advance requests</span>');
            } else {
                const advRows = tour.driver_advances.map(a => `
                    <tr>
                        <td>${escapeHtml(a.driver_name || a.driver)}</td>
                        <td class="text-end">${formatMoney(a.requested_amount)}</td>
                        <td>${statusBadge(a.status)}</td>
                        <td>${formatDate(a.request_date)}</td>
                    </tr>
                `).join('');
                $('#detail_advances').html(`
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="table-light">
                            <tr><th>Driver</th><th class="text-end">Amount</th><th>Status</th><th>Advance Requested</th></tr>
                        </thead>
                        <tbody>${advRows}</tbody>
                    </table>
                `);
            }

            // Tickets / entrance fees
            if (!tour.entrance_fees || tour.entrance_fees.length === 0) {
                $('#detail_tickets').html('<span class="detail-empty">No required entrance tickets (Sigiriya / Polonnaruwa)</span>');
            } else {
                const pax = parseInt(tour.passenger_count, 10) || 0;
                let grandTotal = 0;

                const ticketRows = tour.entrance_fees.map(t => {
                    const unitPrice = parseFloat(t.unit_price) || 0;
                    const lineTotal = pax * unitPrice;
                    grandTotal += lineTotal;
                    return `
                        <tr>
                            <td>${escapeHtml(t.place_name || 'Unknown')}</td>
                            <td>${formatDate(t.tour_date)}</td>
                            <td class="text-end">${pax} &times; ${formatMoney(unitPrice)}</td>
                            <td class="text-end">${formatMoney(lineTotal)}</td>
                        </tr>
                    `;
                }).join('');

                $('#detail_tickets').html(`
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="table-light">
                            <tr><th>Place</th><th>Date</th><th class="text-end">Pax &times; Rate</th><th class="text-end">Total</th></tr>
                        </thead>
                        <tbody>${ticketRows}</tbody>
                    </table>
                    <div class="text-end fw-bold mt-2">Grand Total: ${formatMoney(grandTotal)}</div>
                `);
            }

            // Driver-submitted mileage log (Ongoing and Completed Tours)
            if (!tour.daily_mileage || tour.daily_mileage.length === 0) {
                $('#detail_mileage').html('<span class="detail-empty">No meter readings submitted yet</span>');
            } else {
                const mileageLocked = hasFullMileageCoverage(tour);
                const mileageRows = tour.daily_mileage.map(m => `
                    <tr>
                        <td>${formatDate(m.date)}${dayBadge(tour.start_date, m.date)}</td>
                        <td>${escapeHtml(m.driver_name || m.driver_acc)}</td>
                        <td class="text-end">${mileageKmCellHtml(m.start_km, m.start_id, tour.tour_id, mileageLocked)}</td>
                        <td class="text-end">${mileageKmCellHtml(m.end_km, m.end_id, tour.tour_id, mileageLocked)}</td>
                        <td class="text-end">${formatMoney(m.distance)}</td>
                    </tr>
                `).join('');
                $('#detail_mileage').html(`
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="table-light">
                            <tr><th>Date</th><th>Driver</th><th class="text-end">Start KM</th><th class="text-end">End KM</th><th class="text-end">Distance</th></tr>
                        </thead>
                        <tbody>${mileageRows}</tbody>
                    </table>
                    <div class="text-end mt-2">Estimated Mileage: ${formatMoney(tour.back_office_total_estimate_mileage)} km</div>
                    <div class="text-end fw-bold">Total: ${formatMoney(tour.total_distance)} km</div>
                `);
            }

            // Driver-submitted invoices (Ongoing and Completed Tours)
            if (!tour.expenses || tour.expenses.length === 0) {
                $('#detail_invoices').html('<span class="detail-empty">No invoices submitted yet</span>');
            } else {
                const invoiceRows = tour.expenses.map(e => `
                    <tr>
                        <td>${formatDate(e.expense_date)}</td>
                        <td>${escapeHtml(e.category_label || e.category)}</td>
                        <td>${escapeHtml(e.supplier_name || '-')}</td>
                        <td>${escapeHtml(e.invoice_no || '-')}</td>
                        <td class="text-end">${formatMoney(e.lkr_amount)}</td>
                    </tr>
                `).join('');
                $('#detail_invoices').html(`
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="table-light">
                            <tr><th>Date</th><th>Category</th><th>Supplier</th><th>Invoice #</th><th class="text-end">LKR Amount</th></tr>
                        </thead>
                        <tbody>${invoiceRows}</tbody>
                    </table>
                    <div class="text-end fw-bold mt-2">Total: LKR ${formatMoney(tour.total_lkr)}</div>
                `);
            }

            // Back Office Log Sheet Mileage (Completed Tours only)
            if (!tour.log_sheet_mileage_records || tour.log_sheet_mileage_records.length === 0) {
                $('#detail_back_office_mileage').html('<span class="detail-empty">No log sheet mileage entered</span>');
            } else {
                const mileageRows = tour.log_sheet_mileage_records.map(m => {
                    const driverMatch = (tour.drivers || []).find(d => d.driver === m.transport_agent);
                    const driverLabel = driverMatch ? driverMatch.driver_name : m.transport_agent;
                    const driverMileage = m.is_override ? m.previous_mileage : m.log_sheet_mileage;
                    return `
                        <tr>
                            <td>${escapeHtml(driverLabel)}</td>
                            <td class="text-end">${formatMoney(driverMileage)} km</td>
                            <td class="text-end">${formatMoney(m.estimate_mileage)} km</td>
                            <td class="text-end">${formatMoney(m.log_sheet_mileage)} km</td>
                        </tr>
                    `;
                }).join('');
                $('#detail_back_office_mileage').html(`
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="table-light">
                            <tr><th>Driver</th><th class="text-end">Driver Mileage</th><th class="text-end">Estimated Mileage</th><th class="text-end">Amended Mileage</th></tr>
                        </thead>
                        <tbody>${mileageRows}</tbody>
                    </table>
                    <div class="text-end fw-bold mt-2">Total: ${formatMoney(tour.back_office_total_mileage)} km</div>
                `);
            }

            // Back Office Expenses (Completed Tours only)
            if (!tour.back_office_expenses || tour.back_office_expenses.length === 0) {
                $('#detail_back_office').html('<span class="detail-empty">No back office expenses entered</span>');
            } else {
                const backOfficeRows = tour.back_office_expenses.map(e => `
                    <tr>
                        <td>${formatDate(e.expense_date)}</td>
                        <td>${escapeHtml(e.category_label || e.category)}</td>
                        <td>${escapeHtml(e.supplier_name || '-')}</td>
                        <td>${escapeHtml(e.invoice_no || '-')}</td>
                        <td class="text-end">
                            <div>${formatMoney(e.lkr_amount)}</div>
                            <div>${backOfficeStatusBadge(e.normalized_status)}</div>
                        </td>
                    </tr>
                `).join('');
                $('#detail_back_office').html(`
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="table-light">
                            <tr><th>Date</th><th>Category</th><th>Supplier</th><th>Invoice #</th><th class="text-end">LKR Amount</th></tr>
                        </thead>
                        <tbody>${backOfficeRows}</tbody>
                    </table>
                    <div class="text-end fw-bold mt-2">Total: LKR ${formatMoney(tour.back_office_total_lkr)}</div>
                `);
            }

            $('#tourDetailModal').modal('show');
        }

        function formatDateForInput(date) {
            return date.toISOString().slice(0, 10);
        }

        // Tours can have more than one driver (e.g. one driver covers days 1-3, another covers
        // day 4). Admin actions (finalize / add mileage / add expense) act on behalf of ONE
        // driver at a time, so #actionDriverSelect lets the admin pick which one when a tour has
        // multiple drivers. Single-driver tours keep the old behavior — dropdown stays hidden and
        // that one driver is used automatically.
        function populateActionDriverSelect(tour, showControls) {
            const $select = $('#actionDriverSelect');
            const drivers = tour.drivers || [];

            if (!showControls || drivers.length === 0) {
                $select.hide().empty();
                return;
            }

            if (drivers.length === 1) {
                $select.hide().html(`<option value="${escapeHtml(drivers[0].driver)}">${escapeHtml(drivers[0].driver_name || drivers[0].driver)}</option>`);
                return;
            }

            const previouslySelected = $select.val();
            $select.html(drivers.map(d => `<option value="${escapeHtml(d.driver)}">${escapeHtml(d.driver_name || d.driver)}</option>`).join(''));
            if (previouslySelected && drivers.some(d => d.driver === previouslySelected)) {
                $select.val(previouslySelected);
            }
            $select.show();
        }

        function selectedDriverAcc() {
            return $('#actionDriverSelect').val() || null;
        }

        function isDriverFinalized(tour, driverAcc) {
            const records = tour.finalize_records || [];
            return records.some(r => r.driver_acc === driverAcc);
        }

        function refreshDriverActionState() {
            const tour = currentDetailTour;
            if (!tour) return;

            const driver = selectedDriverAcc();
            const showsFinalizeControls = currentDetailSource === 'ongoing' || currentDetailSource === 'completed';
            const finalized = driver ? isDriverFinalized(tour, driver) : false;

            $('#finalizedBadge').toggle(showsFinalizeControls && finalized);
            $('#finalizeTourBtn').toggle(showsFinalizeControls && !finalized && !!driver);

            const canAddMileage = showsFinalizeControls && !finalized && driver && !hasFullMileageCoverageForDriver(tour, driver);
            $('#addMileageBtn, #addExpenseBtn').toggle(!!canAddMileage);
        }

        function dayBadge(tourStartDate, rowDate) {
            if (!tourStartDate || !rowDate) return '';
            const start = new Date(tourStartDate);
            const current = new Date(rowDate);
            if (isNaN(start.getTime()) || isNaN(current.getTime())) return '';
            const dayNumber = Math.floor((current - start) / (1000 * 60 * 60 * 24)) + 1;
            if (dayNumber < 1) return '';
            return `<span class="badge bg-info text-dark ms-1">Day ${dayNumber}</span>`;
        }

        // Whole-tour calendar coverage — used only to lock/unlock the delete button on the
        // mileage table once every day of the tour (across all drivers combined) has readings.
        function hasFullMileageCoverage(tour) {
            if (!tour.start_date || !tour.end_date) return false;

            const readingsByDate = {};
            (tour.daily_mileage || []).forEach(m => {
                if (!m.date) return;
                const dateKey = m.date.split('T')[0];
                readingsByDate[dateKey] = readingsByDate[dateKey] || {};
                if (m.start_km !== null && m.start_km !== undefined) readingsByDate[dateKey].start = true;
                if (m.end_km !== null && m.end_km !== undefined) readingsByDate[dateKey].end = true;
            });

            const cursor = new Date(tour.start_date.split('T')[0]);
            const end = new Date(tour.end_date.split('T')[0]);
            if (isNaN(cursor.getTime()) || isNaN(end.getTime())) return false;

            while (cursor <= end) {
                const dateKey = cursor.toISOString().split('T')[0];
                const day = readingsByDate[dateKey];
                if (!day || !day.start || !day.end) {
                    return false;
                }
                cursor.setDate(cursor.getDate() + 1);
            }

            return true;
        }

        // A driver's "days" are whatever dates they've submitted readings for — tours can be
        // split across multiple drivers, so this only checks THIS driver's own days are complete
        // (both start and end present), not every calendar day of the whole tour. Mirrors the
        // same scoping used server-side in DriverPortalController::getDaysMissingMeterReadings().
        // Used to gate the "Add Mileage"/"Finalize" controls for the currently-selected driver.
        function hasFullMileageCoverageForDriver(tour, driverAcc) {
            const readings = (tour.daily_mileage || []).filter(m => m.driver_acc === driverAcc);
            if (readings.length === 0) return false;

            return readings.every(m =>
                m.start_km !== null && m.start_km !== undefined &&
                m.end_km !== null && m.end_km !== undefined
            );
        }

        function reloadOngoingDetail() {
            reloadCurrentDetail();
        }

        function reloadCurrentDetail() {
            const source = currentDetailSource;
            const endpoint = source === 'completed' ? 'completed_tours' : (source === 'upcoming' ? 'upcoming_tours' : 'ongoing_tours');

            $.ajax({
                url: '<?= base_url('dashboard2/transport/transport_portal/') ?>' + endpoint,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    const data = (response && response.data) ? response.data : [];
                    if (source === 'completed') {
                        completedToursData = data;
                        renderCompletedTable();
                    } else if (source === 'upcoming') {
                        toursData = data;
                        renderTable();
                    } else {
                        ongoingToursData = data;
                        renderOngoingTable();
                    }

                    const dataSource = source === 'completed' ? completedToursData : (source === 'upcoming' ? toursData : ongoingToursData);
                    const refreshed = dataSource.find(t => t.tour_id === currentDetailTour.tour_id);
                    if (refreshed) {
                        showTourDetail(dataSource.indexOf(refreshed), source);
                    }
                }
            });
        }

        function mileageKmCellHtml(km, readingId, tourId, locked) {
            if (km === null || km === undefined) {
                return '-';
            }
            const value = formatMoney(km);
            if (!readingId || locked) {
                return value;
            }
            return `
                <span class="d-inline-flex align-items-center gap-1">
                    ${value}
                    <button type="button" class="btn btn-sm btn-link text-danger p-0 no-print" onclick="deleteMeterReading(${readingId}, '${tourId}')" title="Delete this reading">
                        <i class="bi bi-trash"></i>
                    </button>
                </span>
            `;
        }

        function deleteMeterReading(readingId, tourId) {
            if (!confirm('Delete this meter reading? The driver will be able to resubmit it afterwards.')) {
                return;
            }

            $.ajax({
                url: '<?= base_url('dashboard2/transport/transport_portal/delete_meter_reading') ?>',
                type: 'POST',
                data: { id: readingId, tour_id: tourId },
                dataType: 'json',
                success: function(response) {
                    if (response && response.status === 200) {
                        reloadCurrentDetail();
                    } else {
                        alert((response && response.message) || 'Failed to delete reading.');
                    }
                },
                error: function() {
                    alert('Failed to delete reading.');
                }
            });
        }

        function finalizeTour() {
            const driver = selectedDriverAcc();
            if (!driver) {
                alert('No driver allocated to this tour.');
                return;
            }

            if (!confirm('Finalize this tour? This locks the driver\'s log sheet and expenses — it cannot be undone or resubmitted afterwards.')) {
                return;
            }

            $('#finalizeTourBtn').prop('disabled', true);

            $.ajax({
                url: '<?= base_url('dashboard2/transport/transport_portal/admin_finalize_tour') ?>',
                type: 'POST',
                data: { tour_id: currentDetailTour.tour_id, driver_acc: driver },
                dataType: 'json',
                success: function(response) {
                    if (response && response.status === 200) {
                        reloadCurrentDetail();
                    } else {
                        alert((response && response.message) || 'Failed to finalize tour.');
                        $('#finalizeTourBtn').prop('disabled', false);
                    }
                },
                error: function() {
                    alert('Failed to finalize tour.');
                    $('#finalizeTourBtn').prop('disabled', false);
                }
            });
        }

        function toggleAddMileageForm() {
            const $form = $('#addMileageForm');
            if ($form.is(':visible')) {
                $form.hide().empty();
                return;
            }
            $('#addExpenseForm').hide().empty();

            const today = formatDateForInput(new Date());
            $form.html(`
                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label mb-0"><small>Date</small></label>
                        <input type="date" class="form-control form-control-sm" id="mileage_date" value="${today}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label mb-0"><small>Type</small></label>
                        <select class="form-select form-select-sm" id="mileage_type">
                            <option value="start">Start</option>
                            <option value="end">End</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label mb-0"><small>Meter Reading</small></label>
                        <input type="number" step="0.01" class="form-control form-control-sm" id="mileage_km">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label mb-0"><small>Photo</small></label>
                        <input type="file" accept="image/*" class="form-control form-control-sm" id="mileage_photo">
                    </div>
                    <div class="col-md-2 d-flex gap-1">
                        <button type="button" class="btn btn-sm btn-success" id="mileage_save_btn"><i class="bi bi-check-lg"></i></button>
                    </div>
                </div>
                <div class="text-danger small mt-1" id="mileage_form_error"></div>
            `);
            $form.show();

            $('#mileage_save_btn').on('click', submitAdminMileage);
        }

        function submitAdminMileage() {
            const driver = selectedDriverAcc();
            const date = $('#mileage_date').val();
            const type = $('#mileage_type').val();
            const km = $('#mileage_km').val();
            const photo = $('#mileage_photo')[0].files[0];

            if (!driver || !date || !type || km === '') {
                $('#mileage_form_error').text('Please fill all required fields.');
                return;
            }

            const formData = new FormData();
            formData.append('tour_id', currentDetailTour.tour_id);
            formData.append('driver_acc', driver);
            formData.append('reading_date', date);
            formData.append('type', type);
            formData.append('km', km);
            if (photo) formData.append('photo', photo);

            $('#mileage_save_btn').prop('disabled', true);

            $.ajax({
                url: '<?= base_url('dashboard2/transport/transport_portal/admin_save_meter_reading') ?>',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    if (response && response.status === 200) {
                        reloadOngoingDetail();
                    } else {
                        $('#mileage_form_error').text((response && response.message) || 'Failed to save reading.');
                        $('#mileage_save_btn').prop('disabled', false);
                    }
                },
                error: function() {
                    $('#mileage_form_error').text('Failed to save reading.');
                    $('#mileage_save_btn').prop('disabled', false);
                }
            });
        }

        function toggleAddExpenseForm() {
            const $form = $('#addExpenseForm');
            if ($form.is(':visible')) {
                $form.hide().empty();
                return;
            }
            $('#addMileageForm').hide().empty();

            const today = formatDateForInput(new Date());
            $form.html(`
                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label mb-0"><small>Date</small></label>
                        <input type="date" class="form-control form-control-sm" id="expense_date" value="${today}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label mb-0"><small>Category</small></label>
                        <select class="form-select form-select-sm" id="expense_category">
                            <option value="6-9-23-10640">Entrance Fee</option>
                            <option value="6-9-23-10641">Other</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label mb-0"><small>Supplier</small></label>
                        <select class="form-select form-select-sm" id="expense_supplier"></select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label mb-0"><small>Invoice #</small></label>
                        <input type="text" class="form-control form-control-sm" id="expense_invoice_no">
                    </div>
                </div>
                <div class="row g-2 align-items-end mt-1">
                    <div class="col-md-2">
                        <label class="form-label mb-0"><small>Dollar Rate</small></label>
                        <input type="number" step="0.01" class="form-control form-control-sm" id="expense_dollar_rate">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label mb-0"><small>Dollar Amount</small></label>
                        <input type="number" step="0.01" class="form-control form-control-sm" id="expense_dollar_amount">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label mb-0"><small>Invoice Photo</small></label>
                        <input type="file" accept="image/*" class="form-control form-control-sm" id="expense_photo">
                    </div>
                    <div class="col-md-1 d-flex gap-1">
                        <button type="button" class="btn btn-sm btn-success" id="expense_save_btn"><i class="bi bi-check-lg"></i></button>
                    </div>
                </div>
                <div class="text-danger small mt-1" id="expense_form_error"></div>
            `);
            $form.show();

            loadExpenseAccounts($('#expense_category').val());
            $('#expense_category').on('change', function() {
                loadExpenseAccounts($(this).val());
            });
            $('#expense_save_btn').on('click', submitAdminExpense);
        }

        function loadExpenseAccounts(category) {
            $.ajax({
                url: '<?= base_url('dashboard2/transport/transport_portal/expense_accounts') ?>',
                type: 'GET',
                data: { category: category },
                dataType: 'json',
                success: function(response) {
                    const accounts = (response && response.data) ? response.data : [];
                    $('#expense_supplier').html(accounts.map(a => `<option value="${escapeHtml(a.L_Code)}">${escapeHtml(a.PName)}</option>`).join(''));
                }
            });
        }

        function submitAdminExpense() {
            const driver = selectedDriverAcc();
            const date = $('#expense_date').val();
            const category = $('#expense_category').val();
            const supplier = $('#expense_supplier').val();
            const invoiceNo = $('#expense_invoice_no').val();
            const dollarRate = $('#expense_dollar_rate').val();
            const dollarAmount = $('#expense_dollar_amount').val();
            const photo = $('#expense_photo')[0].files[0];

            if (!driver || !date || !category || !supplier || !invoiceNo) {
                $('#expense_form_error').text('Please fill all required fields.');
                return;
            }

            const formData = new FormData();
            formData.append('tour_id', currentDetailTour.tour_id);
            formData.append('driver_acc', driver);
            formData.append('expense_date', date);
            formData.append('category', category);
            formData.append('supplier', supplier);
            formData.append('invoice_no', invoiceNo);
            formData.append('dollar_rate', dollarRate);
            formData.append('dollar_amount', dollarAmount);
            if (photo) formData.append('invoice_image', photo);

            $('#expense_save_btn').prop('disabled', true);

            $.ajax({
                url: '<?= base_url('dashboard2/transport/transport_portal/admin_save_expense') ?>',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    if (response && response.status === 200) {
                        reloadOngoingDetail();
                    } else {
                        $('#expense_form_error').text((response && response.message) || 'Failed to save expense.');
                        $('#expense_save_btn').prop('disabled', false);
                    }
                },
                error: function() {
                    $('#expense_form_error').text('Failed to save expense.');
                    $('#expense_save_btn').prop('disabled', false);
                }
            });
        }

        $(document).ready(function() {
            const today = new Date();
            const in30Days = new Date();
            in30Days.setDate(today.getDate() + 30);
            const in2WeeksAgo = new Date();
            in2WeeksAgo.setDate(today.getDate() - 14);

            $('#filter_start_date_from').val(formatDateForInput(today));
            $('#filter_start_date_to').val(formatDateForInput(in30Days));

            $('#completed_start_date_from').val(formatDateForInput(in2WeeksAgo));
            $('#completed_start_date_to').val(formatDateForInput(today));

            loadUpcomingTours();
            loadOngoingTours();
            loadCompletedTours();

            $('#filterApplyBtn').on('click', function() {
                loadUpcomingTours();
            });

            $('#filterClearBtn').on('click', function() {
                $('#filter_start_date_from').val(formatDateForInput(today));
                $('#filter_start_date_to').val(formatDateForInput(in30Days));
                loadUpcomingTours();
            });

            $('#completedFilterApplyBtn').on('click', function() {
                loadCompletedTours();
            });

            $('#completedFilterClearBtn').on('click', function() {
                $('#completed_start_date_from').val(formatDateForInput(in2WeeksAgo));
                $('#completed_start_date_to').val(formatDateForInput(today));
                loadCompletedTours();
            });

            $(document).on('click', '.upcoming-tour-row', function() {
                const index = $(this).data('index');
                showTourDetail(index, 'upcoming');
            });

            $(document).on('click', '.ongoing-tour-row', function() {
                const index = $(this).data('index');
                showTourDetail(index, 'ongoing');
            });

            $(document).on('click', '.completed-tour-row', function() {
                const index = $(this).data('index');
                showTourDetail(index, 'completed');
            });

            $('#addMileageBtn').on('click', toggleAddMileageForm);
            $('#addExpenseBtn').on('click', toggleAddExpenseForm);
            $('#finalizeTourBtn').on('click', finalizeTour);
            $('#actionDriverSelect').on('change', function() {
                $('#addMileageForm, #addExpenseForm').hide().empty();
                refreshDriverActionState();
            });
        });
    </script>
</body>
</html>
