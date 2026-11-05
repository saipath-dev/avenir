<?= view('layouts/head') ?>

<body>

<?= view('layouts/header') ?>
<?= view('layouts/sidebar') ?>

<style>
    .badge {
        padding: 5px 10px;
        border-radius: 3px;
        font-size: 12px;
        font-weight: 500;
    }
    .badge-success {
        background-color: #78b848;
        color: white;
    }
    .action-buttons .btn {
        margin-right: 5px;
        padding: 5px 10px;
        font-size: 12px;
    }
</style>

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <!-- Page Title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Consumption Notes</h4>
                        <div class="page-title-right">
                            <a href="<?= base_url('inventory/usage_note/create') ?>" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Create New Consumption Note
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Usage Notes Table -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title mb-4">All Consumption Notes</h4>

                            <div class="table-responsive">
                                <table id="usage_notes_table" class="table table-bordered table-hover dt-responsive nowrap w-100">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Consumption Note No</th>
                                            <th>Date</th>
                                            <th>Tour ID</th>
                                            <th>Manual ID</th>
                                            <th>Tour Name</th>
                                            <th>Pax</th>
                                            <th>Created By</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($usage_notes)): ?>
                                            <?php $serial = 1; ?>
                                            <?php foreach ($usage_notes as $note): ?>
                                                <tr>
                                                    <td><?= $serial++; ?></td>
                                                    <td>
                                                        <strong><?= esc($note['usage_note_no']); ?></strong>
                                                    </td>
                                                    <td><?= date('Y-m-d', strtotime($note['date'])); ?></td>
                                                    <td><?= esc($note['tour_id']); ?></td>
                                                    <td><?= esc($note['manual_id'] ?? 'N/A'); ?></td>
                                                    <td><?= esc($note['tour_name'] ?? 'N/A'); ?></td>
                                                    <td><?= esc($note['passenger_count'] ?? 'N/A'); ?></td>
                                                    <td><?= esc($note['created_by_name'] ?? 'N/A'); ?></td>
                                                    <td>
                                                        <?php if ($note['status'] === 'pending'): ?>
                                                            <span class="badge bg-warning text-dark">
                                                                <i class="fas fa-clock"></i> Pending
                                                            </span>
                                                        <?php elseif ($note['status'] === 'approved'): ?>
                                                            <span class="badge bg-success">
                                                                <i class="fas fa-check-circle"></i> Approved
                                                            </span>
                                                        <?php elseif ($note['status'] === 'rejected'): ?>
                                                            <span class="badge bg-danger">
                                                                <i class="fas fa-times-circle"></i> Rejected
                                                            </span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="action-buttons">
                                                        <button class="btn btn-sm btn-primary view-details-btn"
                                                                data-usage-note="<?= esc($note['usage_note_no']); ?>"
                                                                title="View Details">
                                                            <i class="fas fa-eye"></i> View
                                                        </button>
                                                        <a href="<?= base_url('inventory/usage_note/print/' . $note['usage_note_no']); ?>"
                                                           class="btn btn-sm btn-info"
                                                           target="_blank"
                                                           title="Print">
                                                            <i class="fas fa-print"></i> Print
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="10" class="text-center">No consumption notes found</td>
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
    </div>
</div>

<!-- View Details Modal -->
<div id="viewDetailsModal" style="display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4);">
    <div style="background-color: #fefefe; margin: 5% auto; padding: 0; border: 1px solid #888; width: 80%; max-width: 900px; border-radius: 5px; box-shadow: 0 4px 8px rgba(0,0,0,0.2);">
        <div style="padding: 15px 20px; background-color: #f8f9fa; border-bottom: 1px solid #dee2e6; border-radius: 5px 5px 0 0;">
            <h5 style="margin: 0; display: inline-block;">Consumption Note Details</h5>
            <button type="button" id="closeModalBtn" style="float: right; background: none; border: none; font-size: 28px; font-weight: bold; color: #aaa; cursor: pointer;">&times;</button>
        </div>
        <div style="padding: 20px;">
            <div id="modal-loading" class="text-center">
                <div style="border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 20px auto;"></div>
                <p>Loading...</p>
            </div>
            <div id="modal-content" style="display: none;">
                <h6 style="margin-bottom: 15px;"><strong>Consumption Note No:</strong> <span id="modal-usage-note-no"></span></h6>

                <div style="overflow-x: auto;">
                    <table class="table table-bordered table-striped" style="width: 100%; border-collapse: collapse;">
                        <thead style="background-color: #f8f9fa;">
                            <tr>
                                <th style="padding: 10px; border: 1px solid #dee2e6;">#</th>
                                <th style="padding: 10px; border: 1px solid #dee2e6;">Activity</th>
                                <th style="padding: 10px; border: 1px solid #dee2e6;">Activity Date</th>
                                <th style="padding: 10px; border: 1px solid #dee2e6;">Item Code</th>
                                <th style="padding: 10px; border: 1px solid #dee2e6;">Item Description</th>
                                <th style="padding: 10px; border: 1px solid #dee2e6;">Quantity</th>
                            </tr>
                        </thead>
                        <tbody id="modal-items-tbody">
                            <!-- Items will be loaded here -->
                        </tbody>
                        <tfoot>
                            <tr style="background-color: #e9ecef; font-weight: bold;">
                                <th colspan="5" style="padding: 10px; border: 1px solid #dee2e6; text-align: right;">Total Quantity:</th>
                                <th id="modal-total-qty" style="padding: 10px; border: 1px solid #dee2e6;">0</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div id="modal-pagination" style="display:none; justify-content:space-between; align-items:center; margin-top:10px;">
                    <span id="modal-page-info" style="font-size:13px; color:#555;"></span>
                    <div>
                        <button id="modal-prev-btn" class="btn btn-sm btn-outline-secondary me-1" disabled>&#8249; Prev</button>
                        <button id="modal-next-btn" class="btn btn-sm btn-outline-secondary">Next &#8250;</button>
                    </div>
                </div>
            </div>
        </div>
        <div style="padding: 15px 20px; background-color: #f8f9fa; border-top: 1px solid #dee2e6; text-align: right; border-radius: 0 0 5px 5px;">
            <button type="button" id="closeModalBtn2" class="btn btn-secondary">Close</button>
        </div>
    </div>
</div>

<style>
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>

<script>
    var modalAllRows = [];
    var MODAL_PAGE_SIZE = 10;
    var modalCurrentPage = 1;

    function renderModalPage() {
        var total = modalAllRows.length;
        var totalPages = Math.ceil(total / MODAL_PAGE_SIZE);
        var start = (modalCurrentPage - 1) * MODAL_PAGE_SIZE;
        var end = Math.min(start + MODAL_PAGE_SIZE, total);
        var pageRows = modalAllRows.slice(start, end);

        $('#modal-items-tbody').html(pageRows.join(''));
        $('#modal-page-info').text('Showing ' + (start + 1) + ' to ' + end + ' of ' + total + ' entries');
        $('#modal-prev-btn').prop('disabled', modalCurrentPage === 1);
        $('#modal-next-btn').prop('disabled', modalCurrentPage >= totalPages || total === 0);

        if (total > MODAL_PAGE_SIZE) {
            $('#modal-pagination').css('display', 'flex');
        } else {
            $('#modal-pagination').hide();
        }
    }

    function displayDetails(data) {
        $('#modal-loading').hide();
        $('#modal-content').show();

        modalAllRows = [];
        modalCurrentPage = 1;
        var totalQty = 0;
        var serial = 1;

        $.each(data, function(i, item) {
            totalQty += parseFloat(item.quantity);
            var activityDate = item.activity_date ? item.activity_date.split(' ')[0] : 'N/A';
            modalAllRows.push(
                '<tr>' +
                '<td style="padding:10px;border:1px solid #dee2e6;text-align:center;">' + serial++ + '</td>' +
                '<td style="padding:10px;border:1px solid #dee2e6;">' + (item.activity_name || 'N/A') + '</td>' +
                '<td style="padding:10px;border:1px solid #dee2e6;text-align:center;">' + activityDate + '</td>' +
                '<td style="padding:10px;border:1px solid #dee2e6;text-align:center;">' + item.item_code + '</td>' +
                '<td style="padding:10px;border:1px solid #dee2e6;">' + (item.item_description || item.item_code) + '</td>' +
                '<td style="padding:10px;border:1px solid #dee2e6;text-align:center;">' + parseFloat(item.quantity).toFixed(2) + '</td>' +
                '</tr>'
            );
        });

        $('#modal-total-qty').text(totalQty.toFixed(2));
        renderModalPage();
    }

    function showError(message) {
        $('#modal-loading').hide();
        $('#modal-content').html(
            '<div class="alert alert-danger" role="alert">' +
            '<i class="fas fa-exclamation-triangle"></i> ' + message +
            '</div>'
        ).show();
    }

    $(document).ready(function() {
        // Initialize DataTable
        if ($.fn.DataTable) {
            $('#usage_notes_table').DataTable({
                "pageLength": 25,
                "order": [[1, "desc"]],
                "columnDefs": [{ "orderable": false, "targets": 9 }],
                "language": {
                    "search": "Search:",
                    "lengthMenu": "Show _MENU_ entries",
                    "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                    "paginate": { "first": "First", "last": "Last", "next": "Next", "previous": "Previous" }
                }
            });
        }

        // Use event delegation so buttons work after DataTable re-renders
        $(document).on('click', '.view-details-btn', function() {
            var usageNoteNo = $(this).data('usage-note');
            $('#viewDetailsModal').show();
            $('#modal-loading').show();
            $('#modal-content').hide();
            $('#modal-pagination').hide();
            $('#modal-usage-note-no').text(usageNoteNo);

            $.ajax({
                url: '<?= base_url('inventory/usage_note/get_details') ?>',
                type: 'GET',
                data: { usage_note_no: usageNoteNo },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        displayDetails(response.data);
                    } else {
                        showError(response.message || 'Failed to load details');
                    }
                },
                error: function() {
                    showError('Failed to fetch consumption note details');
                }
            });
        });

        $(document).on('click', '#modal-prev-btn', function() {
            if (modalCurrentPage > 1) { modalCurrentPage--; renderModalPage(); }
        });

        $(document).on('click', '#modal-next-btn', function() {
            var totalPages = Math.ceil(modalAllRows.length / MODAL_PAGE_SIZE);
            if (modalCurrentPage < totalPages) { modalCurrentPage++; renderModalPage(); }
        });

        $('#closeModalBtn, #closeModalBtn2').on('click', function() {
            $('#viewDetailsModal').hide();
        });

        $(window).on('click', function(event) {
            if (event.target.id === 'viewDetailsModal') {
                $('#viewDetailsModal').hide();
            }
        });
    });
</script>

<?= view('layouts/inpage_footer') ?>

</body>
</html>
