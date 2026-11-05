<?= view('layouts/head') ?>

<body>
    <style>
        .p-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #495057;
        }

        .table-hover tbody tr:hover {
            background-color: #f8f9fa;
            cursor: pointer;
        }

        #ongoing_tours_table tbody tr.table-danger,
        #ongoing_tours_table tbody tr.table-danger td {
            background-color: #f8d7da !important;
        }

        #ongoing_tours_table tbody tr.table-danger:hover,
        #ongoing_tours_table tbody tr.table-danger:hover td {
            background-color: #f1aeb5 !important;
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

        .driver-chip {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 8px 14px;
            font-weight: 600;
            font-size: 0.9rem;
            color: #212529;
        }

        #detail_advances table th {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.02em;
            color: #6c757d;
        }

        #detail_advances table td,
        #detail_advances table th {
            padding: 5px 5px;
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
    </style>

    <!-- Fixed Header with Logo and Back Button -->
    <?= view('layouts/topbar', ['topbar_back' => base_url('dashboard2/tiles'), 'topbar_home' => false, 'topbar_logout' => false, 'topbar_class' => 'no-print']) ?>

    <!-- Main Content Container -->
    <div class="container-fluid p-4 mt-5">
        <div class="row">
            <div class="col-md-12">
                <h2 class="p-title text-center mb-4">TRANSPORT PANEL - ONGOING TOURS</h2>
            </div>
        </div>

        <div class="row mb-3 no-print">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-end align-items-center gap-2">
                            <span class="mb-0">Tours: <span class="badge bg-success" id="ongoing_tours_count">0</span></span>
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
                        <small>No mileage added</small>
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
                        <div class="col-md-2">
                            <div class="detail-section-title">Tour</div>
                            <div class="fw-semibold" id="detail_tour_name"></div>
                            <small class="text-muted" id="detail_tour_dates"></small>
                        </div>
                        <div class="col-md-2">
                            <div class="detail-section-title">PAX / Template</div>
                            <div class="fw-semibold" id="detail_pax"></div>
                            <small class="text-muted" id="detail_template"></small>
                        </div>
                          <div class="col-md-2">
                        <div class="detail-section-title">Drivers</div>
                        <div id="detail_drivers"></div>
                    </div>
                     <div class="col-md-6">
                        <div class="detail-section-title">Driver Advances</div>
                        <div class="table-responsive" id="detail_advances" style="width: 100%;"></div>
                    </div>
                    </div>

                    <div class="row" id="detail_comparison_section">
                        <div class="col-md-12" id="detail_invoices_section">
                            <div class="card comparison-card comparison-card-driver">
                                <div class="card-header comparison-card-header">
                                    <i class="bi bi-person-fill me-1"></i> Driver Log Sheet Entries
                                </div>
                                <div class="card-body">
                                    <div class="detail-section-title d-flex justify-content-between align-items-center" id="detail_mileage_section">
                                        <span>Mileage</span>
                                        <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2 no-print" id="addMileageBtn" title="Upload meter reading on behalf of driver">
                                            <i class="bi bi-plus-lg"></i> Add
                                        </button>
                                    </div>
                                    <div id="addMileageForm" class="border rounded p-2 mb-2" style="display:none;"></div>
                                    <div class="table-responsive" id="detail_mileage"></div>

                                    <div class="detail-section-title mt-3 d-flex justify-content-between align-items-center">
                                        <span>Expenses</span>
                                        <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2 no-print" id="addExpenseBtn" title="Upload invoice on behalf of driver">
                                            <i class="bi bi-plus-lg"></i> Add
                                        </button>
                                    </div>
                                    <div id="addExpenseForm" class="border rounded p-2 mb-2" style="display:none;"></div>
                                    <div class="table-responsive" id="detail_invoices"></div>
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
        let ongoingToursData = [];
        let ongoingDataTable;
        let currentDetailTour = null;

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

        function formatDateForInput(date) {
            return date.toISOString().slice(0, 10);
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
                const noMileage = !tour.daily_mileage || tour.daily_mileage.length === 0;
                const rowClass = noMileage ? 'table-danger' : '';
                return `
                    <tr data-index="${index}" class="ongoing-tour-row ${rowClass}">
                        <td><strong>${escapeHtml(tour.manual_id)}</strong></td>
                        <td>${escapeHtml(tour.consignee)}</td>
                        <td class="text-center"><span class="badge bg-secondary">${escapeHtml(tour.passenger_count)}</span></td>
                        <td data-order="${escapeHtml(tour.start_date || '')}">${formatDate(tour.start_date)}</td>
                        <td data-order="${escapeHtml(tour.end_date || '')}">${formatDate(tour.end_date)}</td>
                        <td>${driverCellHtml(tour)}</td>
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

        // Tours can have more than one driver (e.g. one driver covers days 1-3, another covers
        // day 4). Admin actions (finalize / add mileage / add expense) act on behalf of ONE
        // driver at a time, so #actionDriverSelect lets the admin pick which one when a tour has
        // multiple drivers. Single-driver tours keep the old behavior — dropdown stays hidden and
        // that one driver is used automatically.
        function populateActionDriverSelect(tour) {
            const $select = $('#actionDriverSelect');
            const drivers = tour.drivers || [];

            if (drivers.length === 0) {
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
            const finalized = driver ? isDriverFinalized(tour, driver) : false;

            $('#finalizedBadge').toggle(finalized);
            $('#finalizeTourBtn').toggle(!finalized && !!driver);

            const canAddMileage = !finalized && driver && !hasFullMileageCoverageForDriver(tour, driver);
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
        // (both start and end present), not every calendar day of the whole tour. Used to gate
        // the "Add Mileage"/"Finalize" controls for the currently-selected driver.
        function hasFullMileageCoverageForDriver(tour, driverAcc) {
            const readings = (tour.daily_mileage || []).filter(m => m.driver_acc === driverAcc);
            if (readings.length === 0) return false;

            return readings.every(m =>
                m.start_km !== null && m.start_km !== undefined &&
                m.end_km !== null && m.end_km !== undefined
            );
        }

        function showTourDetail(index) {
            const tour = ongoingToursData[index];
            if (!tour) return;

            currentDetailTour = tour;
            $('#addMileageForm, #addExpenseForm').hide().empty();

            populateActionDriverSelect(tour);
            refreshDriverActionState();

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

            // Driver-submitted mileage log
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
                    <div class="text-end fw-bold mt-2">Total: ${formatMoney(tour.total_distance)} km</div>
                `);
            }

            // Driver-submitted invoices
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

            $('#tourDetailModal').modal('show');
        }

        function reloadOngoingDetail() {
            $.ajax({
                url: '<?= base_url('dashboard2/transport/transport_portal/ongoing_tours') ?>',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    ongoingToursData = (response && response.data) ? response.data : [];
                    renderOngoingTable();
                    const refreshed = ongoingToursData.find(t => t.tour_id === currentDetailTour.tour_id);
                    if (refreshed) {
                        const idx = ongoingToursData.indexOf(refreshed);
                        showTourDetail(idx);
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
                        reloadOngoingDetail();
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
                        reloadOngoingDetail();
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
            loadOngoingTours();

            $(document).on('click', '.ongoing-tour-row', function() {
                const index = $(this).data('index');
                showTourDetail(index);
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
