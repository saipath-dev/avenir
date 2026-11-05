<?= view('layouts/head') ?>

<style>
    .bg-purple {
        background-color: #6f42c1 !important;
        color: white !important;
    }
    .bg-teal {
        background-color: #78b848 !important;
        color: white !important;
    }
</style>

<body>
    <div id="layout-wrapper">
        <?= view('layouts/header') ?>
        <?= view('layouts/sidebar') ?>

        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">

                    <!-- Page Title -->
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                                <h4 class="mb-sm-0">Quotation Change Log Report</h4>
                                <div class="page-title-right">
                                    <ol class="breadcrumb m-0">
                                        <li class="breadcrumb-item"><a href="<?= base_url('dashboard') ?>">Dashboard</a></li>
                                        <li class="breadcrumb-item">Reports</li>
                                        <li class="breadcrumb-item active">Change Log</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filters Card -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="card-title mb-0"><i class="bi bi-funnel me-2"></i>Filters</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <label class="form-label">Tour ID / Manual ID / Name</label>
                                            <input type="text" class="form-control" id="tour_search" placeholder="Search tours...">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Change Type</label>
                                            <select class="form-select" id="change_type">
                                                <option value="">All Types</option>
                                                <?php foreach ($change_types as $key => $value): ?>
                                                    <option value="<?= $key ?>"><?= $value ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Action Type</label>
                                            <select class="form-select" id="action_type">
                                                <option value="">All Actions</option>
                                                <?php foreach ($action_types as $action): ?>
                                                    <option value="<?= $action ?>"><?= $action ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">User</label>
                                            <select class="form-select" id="user_filter">
                                                <option value="">All Users</option>
                                                <?php foreach ($users as $user): ?>
                                                    <option value="<?= $user['id'] ?>"><?= $user['user'] ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Date Range</label>
                                            <div class="input-group">
                                                <input type="date" class="form-control" id="date_from">
                                                <input type="date" class="form-control" id="date_to">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mt-3">
                                        <div class="col-12">
                                            <button class="btn btn-primary" id="apply_filters">
                                                <i class="bi bi-search me-1"></i> Apply Filters
                                            </button>
                                            <button class="btn btn-secondary" id="reset_filters">
                                                <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                                            </button>
                                            <button class="btn btn-success" id="export_excel">
                                                <i class="bi bi-file-earmark-excel me-1"></i> Export to Excel
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Results Card -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="bi bi-clock-history me-2"></i>Change History
                                        <span class="badge bg-info ms-2" id="result_count">0</span>
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover table-striped" id="change_log_table">
                                            <thead class="table-light">
                                                <tr>
                                                    <th style="width: 10%">Date/Time</th>
                                                    <th style="width: 10%">Tour ID</th>
                                                    <th style="width: 20%">Consignee</th>
                                                    <th style="width: 12%">Change Type</th>
                                                    <th style="width: 8%">Action</th>
                                                    <th style="width: 20%">Description</th>
                                                    <th style="width: 10%">User</th>
                                                    <th style="width: 10%">Details</th>
                                                </tr>
                                            </thead>
                                            <tbody id="log_table_body">
                                                <tr>
                                                    <td colspan="8" class="text-center text-muted py-4">
                                                        <i class="bi bi-info-circle me-2"></i>
                                                        Click "Apply Filters" to load change logs
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
            </div>
        </div>
    </div>

    <!-- Details Modal -->
    <div class="modal fade" id="detailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Change Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="details_content">
                    <!-- Details will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <?= view('layouts/scripts') ?>

    <script>
        $(document).ready(function() {

            // Load logs on page load
            loadChangeLogs();

            // Apply Filters
            $('#apply_filters').on('click', function() {
                loadChangeLogs();
            });

            // Reset Filters
            $('#reset_filters').on('click', function() {
                $('#tour_search').val('');
                $('#change_type').val('');
                $('#action_type').val('');
                $('#user_filter').val('');
                $('#date_from').val('');
                $('#date_to').val('');
                $('#log_table_body').html(`
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            <i class="bi bi-info-circle me-2"></i>
                            Click "Apply Filters" to load change logs
                        </td>
                    </tr>
                `);
                $('#result_count').text('0');
            });

            // Export to Excel
            $('#export_excel').on('click', function() {
                const params = new URLSearchParams({
                    tour_search: $('#tour_search').val(),
                    change_type: $('#change_type').val(),
                    action_type: $('#action_type').val(),
                    user_id: $('#user_filter').val(),
                    date_from: $('#date_from').val(),
                    date_to: $('#date_to').val()
                });

                window.location.href = '<?= base_url('reports/change-log/export-excel') ?>?' + params.toString();
            });

            // Load Change Logs
            function loadChangeLogs() {
                $.ajax({
                    url: '<?= base_url('reports/change-log/get-data') ?>',
                    type: 'POST',
                    data: {
                        tour_search: $('#tour_search').val(),
                        change_type: $('#change_type').val(),
                        action_type: $('#action_type').val(),
                        user_id: $('#user_filter').val(),
                        date_from: $('#date_from').val(),
                        date_to: $('#date_to').val()
                    },
                    dataType: 'json',
                    beforeSend: function() {
                        $('#log_table_body').html(`
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </td>
                            </tr>
                        `);
                    },
                    success: function(response) {
                        if (response.status === 200) {
                            displayLogs(response.data);
                            $('#result_count').text(response.count);
                        } else {
                            toastr.error(response.message || 'Failed to load change logs');
                        }
                    },
                    error: function() {
                        toastr.error('An error occurred while loading change logs');
                        $('#log_table_body').html(`
                            <tr>
                                <td colspan="8" class="text-center text-danger py-4">
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                    Error loading data
                                </td>
                            </tr>
                        `);
                    }
                });
            }

            // Display Logs
            function displayLogs(logs) {
                const tbody = $('#log_table_body');
                tbody.empty();

                if (logs.length === 0) {
                    tbody.html(`
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                <i class="bi bi-inbox me-2"></i>
                                No change logs found for the selected filters
                            </td>
                        </tr>
                    `);
                    return;
                }

                logs.forEach(log => {
                    const actionBadge = getActionBadge(log.action_type);
                    const changeTypeBadge = getChangeTypeBadge(log.change_type);

                    // Extract additional context info based on change type
                    let additionalInfo = '';
                    if (log.full_data_snapshot) {
                        try {
                            const snapshot = JSON.parse(log.full_data_snapshot);
                            const before = snapshot.before || {};
                            const after = snapshot.after || {};

                            if (log.change_type === 'hotel_date') {
                                // Hotel changes: show hotel name and tour date
                                const hotelName = after.hotel_name || before.hotel_name;
                                const tourDate = after.t_date || after.tour_date || before.tour_date;

                                if (hotelName) {
                                    additionalInfo += `<br><small class="text-primary"><i class="bi bi-building"></i> ${hotelName}</small>`;
                                }
                                if (tourDate) {
                                    additionalInfo += `<br><small class="text-muted"><i class="bi bi-calendar-date"></i> ${tourDate}</small>`;
                                }
                            } else if (log.change_type === 'activity') {
                                // Activity changes: show activity name and tour date
                                const activityName = after.place_name || after.name || before.place_name || before.name;
                                const tourDate = after.tour_date || before.tour_date;

                                if (activityName) {
                                    additionalInfo += `<br><small class="text-info"><i class="bi bi-pin-map"></i> ${activityName}</small>`;
                                }
                                if (tourDate) {
                                    additionalInfo += `<br><small class="text-muted"><i class="bi bi-calendar-date"></i> ${tourDate}</small>`;
                                }
                            } else if (log.change_type === 'sub_location') {
                                // Sub-location changes: show location name and date
                                const locationName = after.location_name || before.location_name;
                                const tourDate = after.date_id || before.date_id;

                                if (locationName) {
                                    additionalInfo += `<br><small class="text-warning"><i class="bi bi-geo-alt"></i> ${locationName}</small>`;
                                }
                                if (tourDate) {
                                    additionalInfo += `<br><small class="text-muted"><i class="bi bi-calendar-date"></i> ${tourDate}</small>`;
                                }
                            }
                        } catch (e) {
                            // Ignore parsing errors
                        }
                    }

                    tbody.append(`
                        <tr>
                            <td><small>${formatDateTime(log.created_at)}</small></td>
                            <td>
                                <strong class="text-primary">${log.tour_manual_id || 'N/A'}</strong><br>
                                <small class="text-muted">${log.tour_id}</small>
                            </td>
                            <td><small>${log.consignee_name || 'N/A'}</small></td>
                            <td>${changeTypeBadge}</td>
                            <td>${actionBadge}</td>
                            <td><small>${log.change_description || 'N/A'}${additionalInfo}</small></td>
                            <td><small>${log.user_name}</small></td>
                            <td>
                                <button class="btn btn-sm btn-info view-details" data-log='${JSON.stringify(log)}'>
                                    <i class="bi bi-eye"></i>
                                </button>
                            </td>
                        </tr>
                    `);
                });

                // View Details Click
                $('.view-details').on('click', function() {
                    const log = JSON.parse($(this).attr('data-log'));
                    showDetails(log);
                });
            }

            // Show Details Modal
            function showDetails(log) {
                // Parse the full data snapshot for smarter comparison
                let changesHtml = '';
                let oldValueJson = 'N/A';
                let newValueJson = 'N/A';

                try {
                    const snapshot = JSON.parse(log.full_data_snapshot);
                    const before = snapshot.before || {};
                    const after = snapshot.after || {};

                    // Build smart changes display
                    const changes = [];

                    // Compare important fields
                    const fieldsToCompare = {
                        'hotel_name': 'Hotel',
                        'hotel': 'Hotel Code',
                        'room_type': 'Room Type',
                        'meal_code': 'Meal Code',
                        'sgl': 'SGL Price',
                        'dbl': 'DBL Price',
                        'tpl': 'TPL Price',
                        'tour_date': 'Tour Date',
                        't_date': 'Date',
                        'from_location': 'From Location',
                        'to_location': 'To Location',
                        'distance': 'Distance',
                        'gdistance': 'Google Distance'
                    };

                    for (const [key, label] of Object.entries(fieldsToCompare)) {
                        if (before[key] !== undefined && after[key] !== undefined && before[key] !== after[key]) {
                            changes.push({
                                field: label,
                                old: before[key] || 'N/A',
                                new: after[key] || 'N/A'
                            });
                        }
                    }

                    if (changes.length > 0) {
                        changesHtml = `
                            <h6 class="mt-3 mb-2">Changes Summary:</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 30%">Field</th>
                                            <th style="width: 35%">Old Value</th>
                                            <th style="width: 35%">New Value</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${changes.map(c => `
                                            <tr>
                                                <td><strong>${c.field}</strong></td>
                                                <td><span class="text-danger">${c.old}</span></td>
                                                <td><span class="text-success">${c.new}</span></td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>
                        `;
                    }

                    // Full JSON for reference
                    if (log.old_value) {
                        oldValueJson = JSON.stringify(JSON.parse(log.old_value), null, 2);
                    }
                    if (log.new_value) {
                        newValueJson = JSON.stringify(JSON.parse(log.new_value), null, 2);
                    }
                } catch (e) {
                    changesHtml = '<p class="text-warning">Unable to parse change details</p>';
                    oldValueJson = log.old_value || 'N/A';
                    newValueJson = log.new_value || 'N/A';
                }

                // Extract hotel info for modal header
                let hotelInfo = '';
                let tourDateInfo = '';
                if (log.change_type === 'hotel_date' && log.full_data_snapshot) {
                    try {
                        const snapshot = JSON.parse(log.full_data_snapshot);
                        const hotelName = snapshot.after?.hotel_name || snapshot.before?.hotel_name;
                        const tourDate = snapshot.after?.t_date || snapshot.after?.tour_date || snapshot.before?.tour_date;

                        if (hotelName) {
                            hotelInfo = hotelName;
                        }
                        if (tourDate) {
                            tourDateInfo = tourDate;
                        }
                    } catch (e) {
                        // Ignore
                    }
                }

                const html = `
                    <table class="table table-bordered">
                        <tr>
                            <th style="width: 30%">Tour ID</th>
                            <td>${log.tour_manual_id} (${log.tour_id})</td>
                        </tr>
                        <tr>
                            <th>Consignee</th>
                            <td>${log.consignee_name || 'N/A'}</td>
                        </tr>
                        ${hotelInfo ? `<tr>
                            <th>Hotel</th>
                            <td><i class="bi bi-building me-2"></i><strong class="text-primary">${hotelInfo}</strong></td>
                        </tr>` : ''}
                        ${tourDateInfo ? `<tr>
                            <th>Tour Date</th>
                            <td><i class="bi bi-calendar-date me-2"></i><strong>${tourDateInfo}</strong></td>
                        </tr>` : ''}
                        <tr>
                            <th>Change Type</th>
                            <td>${getChangeTypeBadge(log.change_type)}</td>
                        </tr>
                        <tr>
                            <th>Action</th>
                            <td>${getActionBadge(log.action_type)}</td>
                        </tr>
                        <tr>
                            <th>Date/Time</th>
                            <td>${formatDateTime(log.created_at)}</td>
                        </tr>
                        <tr>
                            <th>User</th>
                            <td>${log.user_name} (ID: ${log.user_id})</td>
                        </tr>
                        <tr>
                            <th>IP Address</th>
                            <td>${log.ip_address || 'N/A'}</td>
                        </tr>
                        <tr>
                            <th>Description</th>
                            <td>${log.change_description || 'N/A'}</td>
                        </tr>
                    </table>

                    ${changesHtml}

                    <div class="accordion mt-3" id="fullDataAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingFullData">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFullData">
                                    <i class="bi bi-code-square me-2"></i> View Full JSON Data
                                </button>
                            </h2>
                            <div id="collapseFullData" class="accordion-collapse collapse" data-bs-parent="#fullDataAccordion">
                                <div class="accordion-body">
                                    <h6>Old Value (Full JSON):</h6>
                                    <pre class="bg-light p-2" style="max-height: 200px; overflow-y: auto; font-size: 11px;">${oldValueJson}</pre>

                                    <h6 class="mt-3">New Value (Full JSON):</h6>
                                    <pre class="bg-light p-2" style="max-height: 200px; overflow-y: auto; font-size: 11px;">${newValueJson}</pre>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                $('#details_content').html(html);
                $('#detailsModal').modal('show');
            }

            // Helper Functions
            function getActionBadge(action) {
                const badges = {
                    'INSERT': '<span class="badge bg-success">INSERT</span>',
                    'UPDATE': '<span class="badge bg-primary">UPDATE</span>',
                    'DELETE': '<span class="badge bg-danger">DELETE</span>'
                };
                return badges[action] || '<span class="badge bg-secondary">' + action + '</span>';
            }

            function getChangeTypeBadge(type) {
                const badges = {
                    'hotel_date': '<span class="badge bg-info">Hotel/Date</span>',
                    'sub_location': '<span class="badge bg-warning">Sub-Location</span>',
                    'activity': '<span class="badge bg-purple">Activity</span>',
                    'itinerary': '<span class="badge bg-teal">Itinerary</span>',
                    'general': '<span class="badge bg-dark">General</span>',
                    'room_count': '<span class="badge bg-primary">Room Count</span>',
                    'vehicle_rate': '<span class="badge bg-success">Vehicle Rate</span>',
                    'passenger': '<span class="badge bg-danger">Passenger</span>',
                    'tour_creation': '<span class="badge bg-gradient" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">Tour Creation</span>'
                };
                return badges[type] || '<span class="badge bg-secondary">' + type + '</span>';
            }

            function formatDateTime(datetime) {
                const date = new Date(datetime);
                return date.toLocaleString('en-US', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            }
        });
    </script>

</body>
</html>
