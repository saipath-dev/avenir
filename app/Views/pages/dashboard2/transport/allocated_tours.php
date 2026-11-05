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
        }
    </style>

    <!-- Fixed Header with Logo and Back Button -->
    <?= view('layouts/topbar', ['topbar_back' => base_url('dashboard2/tiles'), 'topbar_home' => false, 'topbar_logout' => false]) ?>

    <!-- Main Content Container -->
    <div class="container-fluid p-4 mt-5">
        <!-- Page Header -->
        <div class="row">
            <div class="col-md-12">
                <h2 class="p-title text-center mb-4">MONTHLY TOUR SUMMARY REPORT - ALLOCATED DRIVERS</h2>
            </div>
        </div>

        <!-- Stats Summary Card -->
        <div class="row mb-3">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-0">Total Allocated Tours: <span class="badge bg-success"><?= count($allocated_tours) ?></span></h5>
                            </div>
                            <div class="d-flex gap-2">
                                <button class="btn btn-success" onclick="downloadCSV()">
                                    <i class="bi bi-download"></i> Download CSV
                                </button>
                                <button class="btn btn-primary" onclick="location.reload()">
                                    <i class="bi bi-arrow-clockwise"></i> Refresh
                                </button>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Date Filter Card -->
        <!-- Date Filter Card -->
<div class="row mb-3">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label for="search_start_date_from" class="form-label">Start Date From</label>
                        <input type="date" class="form-control" id="search_start_date_from" value="<?= esc(service('request')->getGet('start_date_from') ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="search_start_date_to" class="form-label">Start Date To</label>
                        <input type="date" class="form-control" id="search_start_date_to" value="<?= esc(service('request')->getGet('start_date_to') ?? '') ?>">
                    </div>
                    <!-- <div class="col-md-3">
                        <label for="search_end_date" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="search_end_date">
                    </div> -->
                    <div class="col-md-3 d-flex gap-2">
                        <button class="btn btn-primary" id="searchBtn">
                            <i class="bi bi-search"></i> Search
                        </button>
                        <button class="btn btn-secondary" onclick="clearDateFilters()">
                            <i class="bi bi-x-circle"></i> Clear
                        </button>
                    </div>
                    
                </div>
            </div>
        </div>
    </div>
</div>

        <!-- Allocated Tours Table Card -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover" id="allocated_tours_table">
                                <thead class="table-success">
                                    <tr>
                                        <th style="width: 10%;">Tour ID</th>
                                        <th style="width: 20%;">Name of PAX / Group</th>
                                        <th style="width: 8%;">PAX Count</th>
                                        <th style="width: 12%;">Tour Start Date</th>
                                        <th style="width: 12%;">Tour End Date</th>
                                        <th style="width: 18%;">Assigned Chauffeur / Guide</th>
                                        <th style="width: 10%;">Remarks</th>
                                        <th style="width: 10%;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($allocated_tours)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center text-muted py-4">
                                                <i class="bi bi-info-circle fs-3"></i>
                                                <p class="mb-0 mt-2">No allocated tours found</p>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($allocated_tours as $tour): ?>
                                            <tr>
                                                <td><strong><?= esc($tour['manual_id']) ?></strong></td>
                                                <td><?= esc($tour['consignee']) ?></td>
                                                <td class="text-center">
                                                    <span class="badge bg-secondary"><?= $tour['passenger_count'] ?></span>
                                                </td>
                                                <td><?= date('d M Y', strtotime($tour['start_date'])) ?></td>
                                                <td><?= date('d M Y', strtotime($tour['end_date'])) ?></td>
                                                <td><?= esc($tour['driver_name'] ?? 'N/A') ?></td>
                                                <td></td>
                                                <td class="text-center">
                                                    <a href="<?= base_url('dashboard2/transport/driver_allow/tour_transport?tour_id=' . $tour['tour_id']) ?>"
                                                       class="btn btn-info btn-sm">
                                                        <i class="bi bi-eye"></i> View Details
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery CDN -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- DataTables CDN -->
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap4.min.js"></script>

    <!-- Initialize DataTable with Search Functionality -->
    <script>
        let table;

        $(document).ready(function() {
            <?php if (!empty($allocated_tours)): ?>
                table = $('#allocated_tours_table').DataTable({
                    "order": [[3, "asc"]], // Sort by start date ascending
                    "pageLength": 25,
                    "language": {
                        "emptyTable": "No allocated tours found",
                        "search": "Search all columns:",
                        "searchPlaceholder": "Tour ID, PAX, Driver..."
                    }
                    // dom default includes search box: 'lfrtip'
                });

                $('#searchBtn').on('click', function() {
    const startDateFrom = $('#search_start_date_from').val();
    const startDateTo = $('#search_start_date_to').val();

    // Build URL with query parameters
    let url = '<?= base_url('dashboard2/transport/driver_allow/allocated_tours') ?>';
    const params = [];

    if (startDateFrom) {
        params.push('start_date_from=' + encodeURIComponent(startDateFrom));
    }
    if (startDateTo) {
        params.push('start_date_to=' + encodeURIComponent(startDateTo));
    }

    if (params.length > 0) {
        url += '?' + params.join('&');
    }

    // Redirect to the URL with parameters
    window.location.href = url;
});
            <?php endif; ?>
        });

        function clearDateFilters() {
            // Reload page without query parameters
            window.location.href = '<?= base_url('dashboard2/transport/driver_allow/allocated_tours') ?>';
        }

        function downloadCSV() {
            // Get current filtered/searched data from DataTable
            let data;
            if (table) {
                // Get filtered data (respects search and date filters)
                data = table.rows({ search: 'applied' }).data().toArray();
            } else {
                // Fallback: get all data from table rows
                data = [];
                $('#allocated_tours_table tbody tr').each(function() {
                    if (!$(this).find('td').hasClass('dataTables_empty')) {
                        let row = [];
                        $(this).find('td').each(function(index) {
                            if (index < 7) { // Exclude the Action column
                                row.push($(this).text().trim());
                            }
                        });
                        data.push(row);
                    }
                });
            }

            if (!data || data.length === 0) {
                alert('No data to download');
                return;
            }

            // CSV headers
            const headers = ['Tour ID', 'Name of PAX / Group', 'PAX Count', 'Tour Start Date', 'Tour End Date', 'Assigned Chauffeur / Guide', 'Remarks'];

            // Build CSV content
            let csvContent = headers.join(',') + '\n';

            data.forEach(function(row) {
                let rowData;
                if (Array.isArray(row)) {
                    rowData = row;
                } else {
                    // Extract data from DataTable row object
                    rowData = [
                        row[0] || '', // Tour ID
                        row[1] || '', // PAX/Group
                        row[2] || '', // PAX Count
                        row[3] || '', // Start Date
                        row[4] || '', // End Date
                        row[5] || '', // Driver
                        row[6] || ''  // Remarks
                    ];
                }

                // Escape fields that contain commas or quotes
                const escapedRow = rowData.map(field => {
                    const fieldStr = String(field).replace(/"/g, '""'); // Escape quotes
                    return fieldStr.includes(',') || fieldStr.includes('"') || fieldStr.includes('\n')
                        ? `"${fieldStr}"`
                        : fieldStr;
                });

                csvContent += escapedRow.join(',') + '\n';
            });

            // Create blob and download
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);

            // Generate filename with current date
            const today = new Date();
            const dateStr = today.getFullYear() + '-' +
                           String(today.getMonth() + 1).padStart(2, '0') + '-' +
                           String(today.getDate()).padStart(2, '0');

            link.setAttribute('href', url);
            link.setAttribute('download', `allocated_tours_${dateStr}.csv`);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    </script>
</body>

</html>
