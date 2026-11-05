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
    .badge-warning {
        background-color: #ffc107;
        color: #000;
    }
    .badge-danger {
        background-color: #dc3545;
        color: white;
    }
    .alert-custom {
        border-left: 4px solid #dc3545;
    }
    .stats-card {
        border-left: 4px solid;
        transition: transform 0.2s;
    }
    .stats-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    .stats-card.low-stock {
        border-left-color: #ffc107;
    }
    .stats-card.out-stock {
        border-left-color: #dc3545;
    }
</style>

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <!-- Page Title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0">Low Stock Items</h4>
                        <div class="page-title-right">
                            <a href="<?= base_url('inventory/stock/') ?>" class="btn btn-primary">
                                <i class="fas fa-list"></i> View All Inventory
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alert Banner -->
            <?php if ($total_items > 0): ?>
            <div class="row mb-4">
                <div class="col-12">
                    <div class="alert alert-warning alert-custom" role="alert">
                        <h5 class="alert-heading">
                            <i class="fas fa-exclamation-triangle"></i> Stock Alert
                        </h5>
                        <p class="mb-0">
                            You have <strong><?= $total_items ?></strong> item(s) requiring attention:
                            <strong class="text-danger"><?= $out_of_stock_count ?></strong> out of stock and
                            <strong class="text-warning"><?= $low_stock_count ?></strong> below reorder level.
                        </p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Summary Statistics -->
            <div class="row mb-4">
                <div class="col-xl-4 col-md-6">
                    <div class="card stats-card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="text-muted mb-1">Total Items Requiring Attention</p>
                                    <h4 class="mb-0"><?= $total_items ?></h4>
                                </div>
                                <div class="flex-shrink-0">
                                    <div class="avatar-sm rounded-circle bg-primary align-items-center justify-content-center d-flex">
                                        <span class="avatar-title bg-primary rounded-circle">
                                            <i class="fas fa-clipboard-list font-size-20 text-white"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 col-md-6">
                    <div class="card stats-card low-stock">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="text-muted mb-1">Low Stock Items</p>
                                    <h4 class="mb-0 text-warning"><?= $low_stock_count ?></h4>
                                </div>
                                <div class="flex-shrink-0">
                                    <div class="avatar-sm rounded-circle bg-warning align-items-center justify-content-center d-flex">
                                        <span class="avatar-title bg-warning rounded-circle">
                                            <i class="fas fa-exclamation-triangle font-size-20 text-dark"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 col-md-6">
                    <div class="card stats-card out-stock">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="text-muted mb-1">Out of Stock Items</p>
                                    <h4 class="mb-0 text-danger"><?= $out_of_stock_count ?></h4>
                                </div>
                                <div class="flex-shrink-0">
                                    <div class="avatar-sm rounded-circle bg-danger align-items-center justify-content-center d-flex">
                                        <span class="avatar-title bg-danger rounded-circle">
                                            <i class="fas fa-times-circle font-size-20 text-white"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Low Stock Items Table -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title mb-4">Items Requiring Immediate Attention</h4>

                            <div class="table-responsive">
                                <table id="low_stock_table" class="table table-bordered table-hover dt-responsive nowrap w-100">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Priority</th>
                                            <th>Item Code</th>
                                            <th>Description</th>
                                            <th>Current Stock</th>
                                            <th>Reorder Level</th>
                                            <th>Status</th>
                                            <th>Shortage</th>
                                            <th>Purchase Price</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($inventory_items)): ?>
                                            <?php $serial = 1; ?>
                                            <?php foreach ($inventory_items as $item): ?>
                                                <?php
                                                    $shortage = $item['reorder_level'] - $item['current_balance'];
                                                    $priority = ($item['current_balance'] <= 0) ? 'Critical' : 'Medium';
                                                    $priorityClass = ($priority === 'Critical') ? 'text-danger' : 'text-warning';
                                                ?>
                                                <tr>
                                                    <td><?= $serial++; ?></td>
                                                    <td>
                                                        <span class="<?= $priorityClass ?>">
                                                            <i class="fas fa-circle"></i> <?= $priority ?>
                                                        </span>
                                                    </td>
                                                    <td><strong><?= esc($item['Stock_Card']); ?></strong></td>
                                                    <td><?= esc($item['item_description']); ?></td>
                                                    <td class="text-center">
                                                        <strong class="<?= ($item['current_balance'] <= 0) ? 'text-danger' : '' ?>">
                                                            <?= number_format($item['current_balance']); ?>
                                                        </strong>
                                                    </td>
                                                    <td class="text-center"><?= $item['reorder_level']; ?></td>
                                                    <td>
                                                        <?php if ($item['stock_status'] === 'Low Stock'): ?>
                                                            <span class="badge badge-warning">Low Stock</span>
                                                        <?php else: ?>
                                                            <span class="badge badge-danger">Out of Stock</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-center">
                                                        <?php if ($shortage > 0): ?>
                                                            <span class="text-danger"><?= number_format($shortage); ?></span>
                                                        <?php else: ?>
                                                            <span class="text-muted">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-end"><?= number_format($item['purchase_price'], 2); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="9" class="text-center">
                                                    <div class="py-4">
                                                        <i class="fas fa-check-circle text-success font-size-48"></i>
                                                        <p class="mt-3 mb-0">Great! No low stock items at the moment.</p>
                                                    </div>
                                                </td>
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

<script>
    $(document).ready(function() {
        // Check if DataTable is available
        if ($.fn.DataTable) {
            // Initialize DataTable
            $('#low_stock_table').DataTable({
                "pageLength": 25,
                "order": [[4, 'asc']], // Order by current stock ascending (most critical first)
                "columnDefs": [
                    { "orderable": false, "targets": 0 }
                ],
                "language": {
                    "search": "Search Items:",
                    "lengthMenu": "Show _MENU_ items per page",
                    "info": "Showing _START_ to _END_ of _TOTAL_ items",
                    "paginate": {
                        "first": "First",
                        "last": "Last",
                        "next": "Next",
                        "previous": "Previous"
                    }
                }
            });
        }
    });
</script>

<?= view('layouts/inpage_footer') ?>
<?= view('layouts/theme_settings') ?>
<?= view('layouts/scripts') ?>

</body>
</html>
