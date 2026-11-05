<?= view('layouts/head') ?>

<body>
    <div id="layout-wrapper">

        <?= view('layouts/header') ?>
        <?= view('layouts/sidebar') ?>

        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">

                    <!-- Page title -->
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box d-flex align-items-center justify-content-between">
                                <h4 class="mb-0">Account Reservation Cards</h4>
                                <div class="page-title-right">
                                    <ol class="breadcrumb m-0">
                                        <li class="breadcrumb-item"><a href="javascript:void(0);">Accounts Reports</a></li>
                                        <li class="breadcrumb-item active">Account Reservation Cards</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Search -->
                    <div class="row clearfix mt-2">
                        <div class="col-md-12">
                            <div class="card p-4">
                                <div class="body">
                                    <form id="search_form" method="get" action="<?= base_url('report/acc_reservation_card_report') ?>">
                                        <div class="row align-items-end">
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label class="form-label" for="manual_id">Manual ID (e.g. ST 1280)</label>
                                                    <input class="form-control" type="text" id="manual_id" name="manual_id"
                                                        placeholder="Enter Manual ID..."
                                                        value="<?= esc($manual_id ?? '') ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="mb-3">
                                                    <button type="submit" class="btn btn-success w-100">
                                                        <i class="bi bi-search"></i> Search
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="mb-3">
                                                    <a href="<?= base_url('report/acc_reservation_card_report') ?>" class="btn btn-secondary w-100">
                                                        <i class="bi bi-x-circle"></i> Reset
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Results -->
                    <?php if ($is_search): ?>
                        <?php if (!empty($resultlist)): ?>
                            <div class="row clearfix mt-2">
                                <div class="col-md-12">
                                    <div class="card p-4">
                                        <div class="body">
                                            <div class="table-responsive">
                                                <table class="table table-hover table-bordered align-middle">
                                                    <thead class="thead-dark">
                                                        <tr>
                                                            <th>#</th>
                                                            <th>Tour ID</th>
                                                            <th>Manual ID</th>
                                                            <th>Pax / Tour Name</th>
                                                            <th>Agent</th>
                                                            <th>Created By</th>
                                                            <th>Created At</th>
                                                            <th>Start Date</th>
                                                            <th>End Date</th>
                                                            <th>Pax Count</th>
                                                            <th>Action</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php $i = 1; foreach ($resultlist as $row): ?>
                                                        <tr>
                                                            <td><?= $i++ ?></td>
                                                            <td><?= esc($row['tour_id']) ?></td>
                                                            <td><?= esc($row['manual_id'] ?? '-') ?></td>
                                                            <td><?= esc($row['pax_name'] ?? '-') ?></td>
                                                            <td><?= esc($row['agent_name'] ?? '-') ?></td>
                                                            <td><?= esc($row['created_by'] ?? '-') ?></td>
                                                            <td><?= !empty($row['create_time']) ? esc($row['create_time']) : '-' ?></td>
                                                            <td><?= !empty($row['start_date']) ? date('Y-m-d', strtotime($row['start_date'])) : '-' ?></td>
                                                            <td><?= !empty($row['end_date']) ? date('Y-m-d', strtotime($row['end_date'])) : '-' ?></td>
                                                            <td><?= esc($row['passenger_count'] ?? '-') ?></td>
                                                            <td>
                                                                <a href="<?= base_url('tour/print/account_reservation_card?tour_id=' . urlencode($row['tour_id'])) ?>"
                                                                   class="btn btn-danger btn-sm"
                                                                   target="_blank">
                                                                    <i class="bi bi-card-text me-1"></i> Print Reservation Card
                                                                </a>
                                                            </td>
                                                        </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="row clearfix mt-2">
                                <div class="col-md-12">
                                    <div class="alert alert-warning" role="alert">
                                        <strong>No results.</strong> No tour found for Manual ID <strong><?= esc($manual_id) ?></strong>.
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                </div>
            </div>

            <?= view('layouts/brand') ?>
        </div>
    </div>

    <?= view('layouts/inpage_footer') ?>
    <?= view('layouts/theme_settings') ?>
    <?= view('layouts/scripts') ?>

</body>
</html>
