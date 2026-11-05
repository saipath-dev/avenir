<?= view('layouts/head') ?>
<style type="text/css">
    body {
        background-color: #f8f9fa;
    }

    .card-header {
        color: white;
        font-weight: bold;
        border-bottom: 2px solid #f8f9fa;
    }

    .table {
        border-collapse: collapse;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
    }

    .table-hover tbody tr:hover {
        background-color: rgba(0, 123, 255, 0.1);
        cursor: pointer;
    }

    .summary-card {
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 15px;
        color: white;
    }

    .summary-card h6 {
        font-size: 0.85rem;
        opacity: 0.9;
        margin-bottom: 5px;
    }

    .summary-card h3 {
        font-weight: 700;
        margin-bottom: 0;
    }
</style>

<body class="bg-light">

    <!-- Begin page -->
    <div id="layout-wrapper">

        <?= view('layouts/header') ?>

        <!-- Start right Content here -->
        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    <!-- Page title -->
                    <div class="row align-items-center mb-2">
                        <div class="col-md-auto">
                            <h4 class="mb-0 font-size-18">Approved Expenses Dashboard</h4>
                            <ol class="breadcrumb m-0 mt-1">
                                <li class="breadcrumb-item"><a href="<?= base_url('tour/tour_accounts') ?>">Tour Accounts</a></li>
                                <li class="breadcrumb-item active">Approved Expenses</li>
                            </ol>
                        </div>
                        <?php if (!empty($bank_balances)): ?>
                        <div class="col d-flex flex-wrap gap-2 align-items-center justify-content-end">
                            <?php foreach ($bank_balances as $bank): ?>
                            <div class="border rounded px-3 py-1 bg-white shadow-sm d-flex align-items-center gap-2">
                                <i class="bi bi-bank text-secondary" style="font-size:0.8rem;"></i>
                                <div>
                                    <div class="text-muted" style="font-size:0.65rem;"><?= esc($bank['PName']) ?></div>
                                    <div class="fw-bold <?= (float)$bank['balance'] < 0 ? 'text-danger' : 'text-success' ?>" style="font-size:0.85rem;">
                                        <?= number_format((float)$bank['balance'], 2) ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Summary Cards -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="summary-card bg-success">
                                <h6><i class="bi bi-check-circle"></i> Approved Tours</h6>
                                <h3><?= count($approvedExpenses ?? []) ?></h3>
                            </div>
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card shadow-lg border-0 rounded">
                                <div class="card-header bg-gradient-success text-white d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0"><i class="bi bi-list-check"></i> Tours with Approved Expenses</h5>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($approvedExpenses)): ?>
                                        <div class="alert alert-info text-center">
                                            <i class="bi bi-info-circle"></i> No approved expenses found.
                                        </div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table id="approvedExpensesTable" class="table table-bordered table-hover align-middle">
                                                <thead class="bg-light">
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Manual ID</th>
                                                        <th>Consignee</th>
                                                        <th>Start Date</th>
                                                        <th>End Date</th>
                                                        <th class="text-center">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php $i = 1; foreach ($approvedExpenses as $expense): ?>
                                                    <tr>
                                                        <td><?= $i++ ?></td>
                                                        <td><strong><?= esc($expense['manual_id'] ?? '-') ?></strong></td>
                                                        <td><?= esc($expense['consignee'] ?? '-') ?></td>
                                                        <td><?= !empty($expense['start_date']) ? date('d M, Y', strtotime($expense['start_date'])) : '-' ?></td>
                                                        <td><?= !empty($expense['end_date']) ? date('d M, Y', strtotime($expense['end_date'])) : '-' ?></td>
                                                        <td class="text-center">
                                                            <a href="<?= base_url('tour/tour_accounts/approved_expenses_report/' . urlencode($expense['manual_id'])) ?>" class="btn btn-primary btn-sm">
                                                                <i class="bi bi-eye"></i> View Details
                                                            </a>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
            <?= view('layouts/inpage_footer') ?>
        </div>
        <!-- end main content-->
    </div>
    <!-- END layout-wrapper -->

    <?= view('layouts/theme_settings') ?>
    <?= view('layouts/scripts') ?>

    <script>
        $(document).ready(function() {
            $('#approvedExpensesTable').DataTable({
                order: [[3, 'asc']], // Sort by start date
                pageLength: 25
            });
        });
    </script>

</body>

</html>
