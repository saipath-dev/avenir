<?= view('layouts/head') ?>

<style>
    .activity-card {
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
        background: #fff;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .activity-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
        padding-bottom: 10px;
        border-bottom: 2px solid #f0f0f0;
    }

    .employee-name {
        font-size: 18px;
        font-weight: bold;
        color: #333;
    }

    .work-hours {
        font-size: 24px;
        font-weight: bold;
        color: #78b848;
    }

    .activity-details {
        display: none;
        margin-top: 10px;
        padding-top: 10px;
        border-top: 1px solid #e0e0e0;
    }

    .activity-item {
        padding: 8px;
        margin: 5px 0;
        background: #f8f9fa;
        border-left: 3px solid #007bff;
        border-radius: 4px;
    }

    .activity-item.login {
        border-left-color: #78b848;
        background: #d4edda;
    }

    .time-badge {
        background: #007bff;
        color: white;
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: bold;
    }

    .summary-box {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 20px;
    }

    .summary-item {
        text-align: center;
    }

    .summary-value {
        font-size: 32px;
        font-weight: bold;
    }

    .summary-label {
        font-size: 14px;
        opacity: 0.9;
    }

    .btn-details {
        cursor: pointer;
        color: #007bff;
        text-decoration: underline;
        font-size: 14px;
    }

    .btn-details:hover {
        color: #0056b3;
    }

    .no-data {
        text-align: center;
        padding: 40px;
        color: #999;
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
                                <h4 class="mb-sm-0">Employee Activity Report</h4>
                                <div class="page-title-right">
                                    <ol class="breadcrumb m-0">
                                        <li class="breadcrumb-item"><a href="<?= base_url('dashboard') ?>">Dashboard</a></li>
                                        <li class="breadcrumb-item">Reports</li>
                                        <li class="breadcrumb-item active">Employee Activity</li>
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
                                        <div class="col-md-4">
                                            <label for="report_date" class="form-label">Select Date <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control" id="report_date" value="<?= date('Y-m-d') ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <label for="employee_filter" class="form-label">Select Employee</label>
                                            <select class="form-select" id="employee_filter">
                                                <option value="all">All Employees</option>
                                                <?php foreach ($employees as $emp): ?>
                                                    <option value="<?= $emp['id'] ?>"><?= $emp['user'] ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">&nbsp;</label>
                                            <button type="button" class="btn btn-primary w-100" id="btnGenerateReport">
                                                <i class="bi bi-search"></i> Generate Report
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Loading Indicator -->
                    <div id="loadingIndicator" class="text-center py-5" style="display: none;">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading activity data...</p>
                    </div>

                    <!-- Results Container -->
                    <div id="reportResults"></div>

                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // Generate report on button click
            $('#btnGenerateReport').click(function() {
                generateReport();
            });

            // Allow enter key to trigger report
            $('#report_date, #employee_filter').keypress(function(e) {
                if (e.which === 13) {
                    generateReport();
                }
            });

            function generateReport() {
                const date = $('#report_date').val();
                const employeeId = $('#employee_filter').val();

                if (!date) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Error',
                        text: 'Please select a date'
                    });
                    return;
                }

                $('#loadingIndicator').show();
                $('#reportResults').html('');

                $.ajax({
                    url: '<?= base_url('reports/employee_activity/get_report') ?>',
                    method: 'POST',
                    data: {
                        date: date,
                        employee_id: employeeId
                    },
                    dataType: 'json',
                    success: function(response) {
                        $('#loadingIndicator').hide();

                        if (response.status === 200) {
                            displayReport(response.data);
                        } else {
                            $('#reportResults').html(`
                                <div class="card">
                                    <div class="card-body">
                                        <div class="no-data">
                                            <i class="bi bi-info-circle" style="font-size: 48px;"></i>
                                            <p>${response.msg || 'No data found'}</p>
                                        </div>
                                    </div>
                                </div>
                            `);
                        }
                    },
                    error: function(xhr, status, error) {
                        $('#loadingIndicator').hide();
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Error loading report: ' + error
                        });
                    }
                });
            }

            function displayReport(data) {
                if (!data || data.length === 0) {
                    $('#reportResults').html(`
                        <div class="card">
                            <div class="card-body">
                                <div class="no-data">
                                    <i class="bi bi-info-circle" style="font-size: 48px;"></i>
                                    <p>No activity found for the selected criteria</p>
                                </div>
                            </div>
                        </div>
                    `);
                    return;
                }

                // Calculate total hours
                let totalHours = 0;
                data.forEach(emp => {
                    totalHours += parseFloat(emp.decimal_hours || 0);
                });

                let html = `
                    <div class="row">
                        <div class="col-12">
                            <div class="summary-box">
                                <div class="row">
                                    <div class="col-md-4 summary-item">
                                        <div class="summary-value">${data.length}</div>
                                        <div class="summary-label">Active Employees</div>
                                    </div>
                                    <div class="col-md-4 summary-item">
                                        <div class="summary-value">${totalHours.toFixed(2)}</div>
                                        <div class="summary-label">Total Hours</div>
                                    </div>
                                    <div class="col-md-4 summary-item">
                                        <div class="summary-value">${(totalHours / data.length).toFixed(2)}</div>
                                        <div class="summary-label">Average Hours</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                `;

                data.forEach(emp => {
                    html += `
                        <div class="activity-card">
                            <div class="activity-header">
                                <div>
                                    <div class="employee-name">${emp.employee}</div>
                                    <small class="text-muted">
                                        Login: ${formatDateTime(emp.login_time)} |
                                        Last Activity: ${formatDateTime(emp.last_activity)}
                                    </small>
                                </div>
                                <div class="text-end">
                                    <div class="work-hours">${emp.formatted_duration}</div>
                                    <small class="text-muted">${emp.decimal_hours} hours</small>
                                </div>
                            </div>
                            <div>
                                <span class="btn-details" onclick="toggleDetails('${emp.employee}')">
                                    <i class="bi bi-chevron-down"></i> View ${emp.activities.length} Activities
                                </span>
                            </div>
                            <div class="activity-details" id="details_${emp.employee.replace(/\s+/g, '_')}">
                                ${emp.activities.map(act => `
                                    <div class="activity-item ${act.Panel.toLowerCase() === 'login' ? 'login' : ''}">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong>${act.Panel}</strong> - ${act.Url}
                                            </div>
                                            <div>
                                                <span class="time-badge">${formatTime(act.Date)}</span>
                                                <small class="text-muted ms-2">${act.ip}</small>
                                            </div>
                                        </div>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    `;
                });

                html += `
                        </div>
                    </div>
                `;

                $('#reportResults').html(html);
            }

            window.toggleDetails = function(employee) {
                const detailsId = 'details_' + employee.replace(/\s+/g, '_');
                $('#' + detailsId).slideToggle();
            };

            function formatDateTime(dateTime) {
                if (!dateTime) return 'N/A';
                const date = new Date(dateTime);
                return date.toLocaleString('en-US', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit'
                });
            }

            function formatTime(dateTime) {
                if (!dateTime) return 'N/A';
                const date = new Date(dateTime);
                return date.toLocaleTimeString('en-US', {
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit'
                });
            }
        });
    </script>
</body>

</html>
