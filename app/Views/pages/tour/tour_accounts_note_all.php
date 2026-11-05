<?php $hideOperationsButtons = (int) (session()->get('group_id')) === 13; ?>
<?= view('layouts/head') ?>
<style type="text/css">
    body {

        background-color: #f8f9fa;
    }

    .card-header {
        /*background: linear-gradient(to right, #007bff, #6610f2);*/
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

    .table-striped tbody tr:nth-of-type(odd) {
        background-color: #f9f9f9;
    }

    .btn {
        transition: 0.3s ease;
    }

    .btn:hover {
        background-color: #0056b3;
        color: white;
    }

    /* Pulse animation for pending expenses button */
    @keyframes pulse {
        0% {
            box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7);
        }
        70% {
            box-shadow: 0 0 0 10px rgba(220, 53, 69, 0);
        }
        100% {
            box-shadow: 0 0 0 0 rgba(220, 53, 69, 0);
        }
    }

    #pendingExpenses {
        border: 2px solid #dc3545;
    }

    #pendingExpenses:hover {
        background-color: #c82333 !important;
        transform: scale(1.05);
    }
</style>

<body class="bg-light">

    <!-- Begin page -->
    <div id="layout-wrapper">

        <?= view('layouts/header') ?>
        <?= view('layouts/sidebar') ?>

        <!-- Start right Content here -->
        <div class="main-content">

            <div class="page-content">
                <div class="container-fluid">

                    <div class="row mb-4">
                        <div class="col-md-9">
                            <h4 class="text-primary font-weight-bold">Payment Forecast Report</h4>
                        </div>
                        <div class="col-md-3 text-end">
                            <h6 class="text-secondary"><?= date('Y-m-d') ?></h6>
                        </div>
                    </div>


                    <!-- Table -->
                    <div class="container-fluid py-4">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card shadow-lg border-0 rounded">
                                    <div class="card-header bg-gradient-light text-dark d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0">Payment Forecast</h5>
                                        <div class="d-flex ms-auto">
                                            <?php if (!$hideOperationsButtons): ?>
                                            <button id="pendingExpenses" class="btn btn-danger btn-sm me-2 shadow-sm" style="<?= ($pending_expenses_count ?? 0) > 0 ? 'animation: pulse 1.5s infinite;' : '' ?> font-weight: bold;" onclick="window.location.href='<?= base_url('tour/tour_accounts/pending_expenses_dashboard') ?>'">
                                                <i class="bi bi-exclamation-triangle-fill"></i> Pending Expenses
                                                <?php if (($pending_expenses_count ?? 0) > 0): ?>
                                                    <span class="badge bg-light text-danger ms-1"><?= $pending_expenses_count ?></span>
                                                <?php endif; ?>
                                            </button>
                                            <?php endif; ?>
                                            <button id="viewAll" class="btn btn-outline-secondary btn-sm me-2" onclick="window.location.href='<?= base_url('tour/tour_accounts') ?>'">
                                                <b>Back to Pending</b>
                                            </button>
                                            <button id="downloadExcel" class="btn btn-outline-primary btn-info btn-sm" onclick="downloadExcel('mainTable')">
                                                <i class="bi bi-file-earmark-arrow-down"></i> <b>Export to Excel</b>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Date Range Filter + Table Search -->
                                    <div class="card-body border-bottom pb-3">
                                        <div class="d-flex justify-content-between align-items-end flex-wrap gap-2">
                                            <form method="GET" action="" class="row g-2 align-items-end m-0">
                                                <?php if (isset($_GET['view_all'])): ?>
                                                    <input type="hidden" name="view_all" value="true">
                                                <?php endif; ?>
                                                <div class="col-auto">
                                                    <label class="form-label mb-1 fw-semibold">Start Date</label>
                                                    <input type="date" name="start_date" class="form-control form-control-sm" value="<?= htmlspecialchars($filter_start_date ?? '') ?>">
                                                </div>
                                                <div class="col-auto">
                                                    <label class="form-label mb-1 fw-semibold">End Date</label>
                                                    <input type="date" name="end_date" class="form-control form-control-sm" value="<?= htmlspecialchars($filter_end_date ?? '') ?>">
                                                </div>
                                                <div class="col-auto">
                                                    <button type="submit" class="btn btn-primary btn-sm">
                                                        <i class="bi bi-funnel-fill"></i> Filter
                                                    </button>
                                                    <?php if (!empty($filter_start_date) || !empty($filter_end_date)): ?>
                                                        <a href="<?= base_url('tour/tour_accounts') ?>" class="btn btn-outline-secondary btn-sm ms-1">
                                                            <i class="bi bi-x-circle"></i> Clear
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </form>

                                            <div class="input-group input-group-sm" style="max-width: 320px;">
                                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                                <input type="text" id="tableSearchInput" class="form-control" placeholder="Search by Manual ID, Tour Name, Hotel...">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-striped table-hover align-middle" id="mainTable">
                                                <thead class="bg-light text-primary">
                                                    <tr>
                                                        <th>#</th>
                                                        <th>Manual ID</th>
                                                        <th>Tour Name</th>
                                                        <th>Start Date</th>
                                                        <th>End Date</th>
                                                        <th>Hotel Name</th>
                                                        <!-- <th>Hotel Date</th> -->
                                                        <th>Bank Account</th>
                                                        <th>Q Amount</th>
                                                        <th>C Amount</th>
                                                        <th>Payable Payment</th>
                                                        <th>Advance Payment</th>
                                                        <th>Balance</th>
                                                        <th>Confirm</th>
                                                        <th>Request</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if (!empty($consignee)) : ?>
                                                        <?php $i = 1;
                                                        $total_hotel = $total_hotel_sgl = $total_hotel_dbl = $total_hotel_tpl = 0;
                                                        $total_hotel_pay = $total_hotel_pay_new = 0; ?>
                                                        <?php foreach ($consignee as $tour) :
                                                            // if($tour['confirm'] != 3):

                                                        ?>
                                                            <tr style="background-color: #eafff5ff; cursor: pointer;" class="tour-header-row" data-tour-id="<?= $tour['tour_id']; ?>">
                                                                <td colspan="2" class="font-italic text-danger"><i class="bi bi-chevron-down tour-toggle-icon me-1"></i><?= $tour['manual_id']; ?>&nbsp;<span class="badge bg-secondary"><i class="bi bi-people-fill"></i> Pax: <?= $tour['passenger_count'] ?? 0; ?></span></td>
                                                                <td colspan="2" class="font-italic"><?= $tour['tour_id']; ?></td>
                                                                <td colspan="2" class="font-italic">Invoice No - <span class="text-danger"><?= $tour['invoice_no']; ?></span></td>
                                                                <td> <a href="<?= base_url('accounts/debtor_invoice_new/print?tour=' . $tour['tour_id']); ?>" target="_blank" class="btn btn-info btn-sm">View Invoice</a> </td>
                                                                <td>Status</td>
                                                                <td>
                                                                    <?php
                                                                    $status = $tour['inv_alert_status'];

                                                                    if ($status == 1) {
                                                                        echo '<span class="badge bg-success">Accepted</span>' . $tour['accepted_user'];
                                                                    } elseif ($status == 2) {
                                                                        echo '<span class="badge  bg-danger">Rejected</span>' . $tour['accepted_user'];
                                                                    } elseif ($status == 3) {
                                                                        echo '<span class="badge  bg-info">Sent</span>' . $tour['accepted_user'];
                                                                    } else {
                                                                        echo '<span class="badge bg-warning text-dark">New Invoice</span>';
                                                                    }
                                                                    ?>
                                                                </td>
                                                                <td colspan="3"></td>
                                                                <?php if ($tour['inv_alert_status'] != 3 && !$hideOperationsButtons) { ?>
                                                                    <td><button
                                                                            class="btn btn-success btn-sm inv-accept-button"
                                                                            data-tour-id="<?= $tour['tour_id']; ?>"
                                                                            data-manual-id="<?= $tour['manual_id']; ?>"
                                                                            data-inv="<?= $tour['invoice_no']; ?>">
                                                                            Accept
                                                                        </button></td>
                                                                    <td>
                                                                        <button
                                                                            class="btn btn-danger btn-sm inv-reject-button"
                                                                            data-tour-id="<?= $tour['tour_id']; ?>"
                                                                            data-manual-id="<?= $tour['manual_id']; ?>"
                                                                            data-inv="<?= $tour['invoice_no']; ?>">
                                                                            Reject
                                                                        </button>
                                                                    </td>
                                                                <?php } else { ?>
                                                                    <td colspan="2"></td>
                                                                <?php } ?>
                                                            </tr>

                                                            <?php
                                                            $pay = $tour['total_transport_cost'] - $tour['advance_dr'] + $tour['expenses_cr'] - $tour['total_pay_cr'];
                                                            ?>
                                                            <?php if (!empty($tour['hotels'])) : ?>
                                                                <?php foreach ($tour['hotels'] as $hotel) :
                                                                    // if (empty($hotel['hotel_advance']) || $hotel['hotel_advance'] == 'NULL' || $hotel['hotel_advance'] == 0) {
                                                                    //     if ($tour['sgl'] == 1) {
                                                                    //         $total_hotel_pay = '$' . $hotel['sgl'] * $tour['sgl'];
                                                                    //     } else if ($tour['dbl'] == 1) {
                                                                    //         $total_hotel_pay = '$' . $hotel['dbl'] * $tour['dbl'];
                                                                    //     } else if ($tour['tpl'] == 1) {
                                                                    //         $total_hotel_pay = '$' . $hotel['tpl'] * $tour['tpl'];
                                                                    //     } else {
                                                                    //         $total_hotel_pay = '$0.00';
                                                                    //     }
                                                                    // } else {
                                                                    //     $total_hotel_pay = 'LKR' . number_format($hotel['hotel_advance'], 2);
                                                                    // }

                                                                    if (empty($hotel['sgl']) && empty($hotel['dbl']) && empty($hotel['tpl'])) {
                                                                        $total_hotel_pay = '$0';
                                                                    } elseif (empty($hotel['hotel_advance']) || $hotel['hotel_advance'] == 'NULL' || $hotel['hotel_advance'] == 0) {
                                                                        $total_hotel_pay = '$' . number_format(($hotel['sgl'] * $tour['sgl']) + ($hotel['dbl'] *  $tour['dbl']) + ($hotel['tpl'] * $tour['tpl']), 2);
                                                                    } else {
                                                                        $total_hotel_pay = 'LKR' . number_format($hotel['hotel_advance'], 2);
                                                                    }


                                                                    // if (empty($hotel['hotel_advance']) || $hotel['hotel_advance'] == 'NULL' || $hotel['hotel_advance'] == 0) {
                                                                    //     if ($tour['sgl'] == 1) {
                                                                    //         $total_hotel_pay_new = $hotel['sgl'] * $tour['sgl'];
                                                                    //     } else if ($tour['dbl'] == 1) {
                                                                    //         $total_hotel_pay_new = $hotel['dbl'] * $tour['dbl'];
                                                                    //     } else if ($tour['tpl'] == 1) {
                                                                    //         $total_hotel_pay_new = $hotel['tpl'] * $tour['tpl'];
                                                                    //     } else {
                                                                    //         $total_hotel_pay_new = 0;
                                                                    //     }
                                                                    // } else {
                                                                    //     $total_hotel_pay_new = number_format($hotel['hotel_advance'], 2);
                                                                    // }

                                                                    if (empty($hotel['sgl']) && empty($hotel['dbl']) && empty($hotel['tpl'])) {
                                                                        $total_hotel_pay_new = 0;
                                                                    } elseif (empty($hotel['hotel_advance']) || $hotel['hotel_advance'] == 'NULL' || $hotel['hotel_advance'] == 0) {
                                                                        $total_hotel_pay_new = ($hotel['sgl'] * $tour['sgl']) + ($hotel['dbl'] * $tour['dbl']) + ($hotel['tpl'] * $tour['tpl']);
                                                                    } else {
                                                                        $total_hotel_pay_new = number_format($hotel['hotel_advance'], 2);
                                                                    }


                                                                    // if ($tour['sgl'] != 0) {
                                                                    $total_hotel_sgl = (float)$hotel['sgl'] * $tour['sgl'];
                                                                    //     $total_hotel_dbl = 0;
                                                                    //     $total_hotel_tpl = 0;
                                                                    // }  
                                                                    // if ($tour['dbl'] != 0) {
                                                                    // $total_hotel_sgl = 0;
                                                                    $total_hotel_dbl = (float)$hotel['dbl'] * $tour['dbl'];
                                                                    //     $total_hotel_tpl = 0;
                                                                    // }  
                                                                    // if ($tour['tpl'] != 0) {
                                                                    // $total_hotel_sgl = 0;$total_hotel_sum = $total_hotel_sgl + $total_hotel_dbl + $total_hotel_tpl;
                                                                    // $total_hotel_dbl = 0;
                                                                    $total_hotel_tpl = (float)$hotel['tpl'] * $tour['tpl'];
                                                                    // }

                                                                    $total_hotel_sum = $total_hotel_sgl + $total_hotel_dbl + $total_hotel_tpl;
                                                                    $total_hotel = '$' . number_format($total_hotel_sum, 2);

                                                                    // hotel_dollar_rate is only set by legacy flows (Hotel Confirmation, HotelManagementController, etc).
                                                                    // For new bookings it stays 0, so fall back to the global daily USD rate.
                                                                    helper('daily_usd_rate');
                                                                    $effective_dollar_rate = ((float) ($hotel['hotel_dollar_rate'] ?? 0)) != 0 ? $hotel['hotel_dollar_rate'] : (get_usd_rate() ?? 0);

                                                                    if ($effective_dollar_rate != 0) {
                                                                        $total_hotel_advance = 'LKR' . number_format($hotel['pay_doller_count'] * $effective_dollar_rate, 2);
                                                                    } else {
                                                                        $total_hotel_advance = 0;
                                                                    }


                                                                    // if ($hotel['pay_doller_count'] == 0) {
                                                                    //     $hotel['hotel_advance'] = 0;
                                                                    // }



                                                                ?>
                                                                    <tr class="tour-detail-row" data-tour-id="<?= $tour['tour_id']; ?>">

                                                                        <td><?= $i; ?></td> <!-- Use $i without incrementing here  #-->
                                                                        <td><?= $tour['manual_id']; ?></td> <!-- Manual ID -->
                                                                        <td><?= $tour['consignee']; ?><br><span class="text-muted">(<?= $tour['create_user']; ?>)</span></td><!-- Tour Name -->
                                                                        <td><?= date('d M, Y', strtotime($tour['start_date'])); ?></td><!-- Start Date -->
                                                                        <td><?= date('d M, Y', strtotime($tour['end_date'])); ?></td><!-- End Date -->
                                                                        <td><?= $hotel['hotel_name']; ?></td><!-- Hotel Name -->

                                                                        <td><?= $hotel['bank_name']; ?><br><?= $hotel['branch_name']; ?><br><?= $hotel['sup_bank_acc']; ?></td><!-- Bank Account -->
                                                                        <td><?= empty($total_hotel) ? '$0.00' : $total_hotel; ?></td><!-- Q Amount -->
                                                                        <td><?= empty($hotel['total_hotel_doller']) ? '$0.00' : '$' . $hotel['total_hotel_doller']; ?></td><!-- C Amount -->
                                                                        <td class="text-primary"><?= $total_hotel_pay; ?></td><!-- Payable Payment -->
                                                                        <td class="text-success">LKR <?= $total_hotel_advance; ?></td><!-- Advance Payment -->
                                                                        <td class="text-danger">LKR <?= number_format(($hotel['pay_doller_count'] * $effective_dollar_rate) - $hotel['hotel_advance'], 2); ?></td><!-- Balance -->
                                                                        <td>
                                                                            <?php if ((($hotel['pay_doller_count'] * $effective_dollar_rate) > 0)  && ($total_hotel_pay > 0)):

                                                                            ?>
                                                                                <button
                                                                                    class="btn btn-success btn-sm complete-button"
                                                                                    data-tour-id="<?= $tour['tour_id']; ?>"
                                                                                    data-hotel-acc="<?= $hotel['hotel_acc']; ?>" <?php if ($hotel['payment_complete'] == 1) {
                                                                                                                                        echo 'disabled';
                                                                                                                                    } else {
                                                                                                                                        echo '';
                                                                                                                                    } ?>>
                                                                                    <?php if ($hotel['payment_complete'] == 1) {
                                                                                        echo 'Completed';
                                                                                    } else {
                                                                                        echo 'Complete';
                                                                                    } ?>
                                                                                </button>
                                                                            <?php endif; ?>
                                                                        </td><!-- complete -->
                                                                        <td>
                                                                            <?php
                                                                            // Default to the actual due balance once one exists (pay_doller_count/hotel_advance
                                                                            // populated by a prior request/payment); otherwise fall back to the quoted Q Amount
                                                                            // so the input isn't stuck at $0.00 for a booking that's never been requested yet.
                                                                            $balanceDue = ($hotel['pay_doller_count'] * $effective_dollar_rate) - $hotel['hotel_advance'];
                                                                            $defaultRequestAmount = $balanceDue > 0 ? $balanceDue : $total_hotel_sum;
                                                                            ?>
                                                                            <div class="d-flex">
                                                                                <label>$ &nbsp;</label><input type="text" class="form-control input-sm request_amount" placeholder="Enter amount" id="input-amount-<?= $tour['tour_id']; ?>" value=" <?= number_format($defaultRequestAmount, 2); ?>" />
                                                                            </div>
                                                                            <div class="d-flex">
                                                                                <?php if (!$hideOperationsButtons): ?>
                                                                                <button
                                                                                    class="btn btn-warning btn-sm request-button"
                                                                                    data-tour-id="<?= $tour['tour_id']; ?>"
                                                                                    data-total-amnt="<?php echo $total_hotel_pay_new  ?>"
                                                                                    total-advance="<?= $total_hotel_advance; ?>"
                                                                                    data-type="Hotel"
                                                                                    data-rate-missing="<?= $effective_dollar_rate == 0 ? '1' : '0' ?>"
                                                                                    total-hotel-due=" <?= number_format(($hotel['pay_doller_count'] * $effective_dollar_rate) - $hotel['hotel_advance'], 2); ?>"
                                                                                    data-total-doller-amt="<?= $total_hotel; ?>"
                                                                                    data-hotel-acc="<?= $hotel['hotel_acc']; ?>" <?php if ($hotel['payment_request'] == 1) {
                                                                                                                                        echo 'disabled';
                                                                                                                                    } else {
                                                                                                                                        echo '';
                                                                                                                                    } ?>><?php if ($hotel['payment_request'] == 1) {
                                                                                                                                                echo 'Requested';
                                                                                                                                            } else {
                                                                                                                                                echo 'Request';
                                                                                                                                            } ?></button>
                                                                                <?php endif; ?>
                                                                            </div>
                                                                            <?php if ($hotel['payment_request'] == 1 && isset($hotel['requested_amount']) && in_array($hotel['pr_status'], [0, 1, 4])): ?>
                                                                                <small class="text-success fw-semibold">$ <?= number_format($hotel['requested_amount'], 2) ?></small>
                                                                            <?php endif; ?>
                                                                        </td>

                                                                    </tr>
                                                                <?php endforeach; ?>

                                                                <?php if (!empty($tour['lunch_payments'])) : ?>
                                                                    <?php foreach ($tour['lunch_payments'] as $lunch) : ?>
                                                                        <tr class="tour-detail-row" data-tour-id="<?= $tour['tour_id']; ?>">
                                                                            <td><?= $i; ?></td>
                                                                            <td><?= $tour['manual_id']; ?></td>
                                                                            <td><?= $tour['consignee']; ?><br><span class="text-muted">(<?= $tour['create_user']; ?>)</span></td>
                                                                            <td><?= date('d M, Y', strtotime($lunch['tour_date'])); ?></td>
                                                                            <td><?= date('d M, Y', strtotime($tour['end_date'])); ?></td>
                                                                            <td><?= $lunch['sup_name']; ?><br><small class="text-muted">Activity Payment</small></td>
                                                                            <td><?= $lunch['bank_name']; ?><br><?= $lunch['branch_name']; ?><br><?= $lunch['sup_bank_acc']; ?></td>
                                                                            <?php
                                                                            $pax = (int)($tour['passenger_count'] ?? 1) ?: 1;
                                                                            $act_display = !empty($lunch['lkr_fee'])
                                                                                ? 'LKR ' . number_format((float)$lunch['lkr_fee'] * $pax, 0)
                                                                                : 0;
                                                                            $act_req_val = !empty($lunch['lkr_fee'])
                                                                                ? (float)$lunch['lkr_fee'] * $pax
                                                                                : (float)$lunch['fee'];
                                                                            $act_zero = !empty($lunch['lkr_fee']) ? 'LKR 0' : '0';
                                                                            ?>
                                                                            <td class="text-primary"> Per pax <br> $ <?= number_format($lunch['fee'], 0); ?>&nbsp;<br> LKR &nbsp;<?= number_format($lunch['lkr_fee'], 0); ?></td>
                                                                            <td><?= !empty($lunch['lkr_fee']) ? 'LKR 0' : '$0'; ?></td>
                                                                            <td class="text-primary"><?= $act_display; ?></td>
                                                                            <td class="text-success"><?= !empty($lunch['lkr_fee']) ? 'LKR 0' : '0'; ?></td>
                                                                            <td class="text-danger"><?= $act_display; ?></td>
                                                                            <td>-</td>
                                                                            <td>
                                                                                <div class="d-flex">
                                                                                    <label>LKR &nbsp;</label>
                                                                                    <input type="text" class="form-control input-sm request_amount" placeholder="Enter amount" id="input-amount-lunch-<?= $lunch['id']; ?>" value="<?= $act_req_val; ?>" />
                                                                                </div>
                                                                                <div class="d-flex">
                                                                                    <?php if (!$hideOperationsButtons): ?>
                                                                                    <button class="btn btn-warning btn-sm request-button"
                                                                                        data-tour-id="<?= $tour['tour_id']; ?>"
                                                                                        data-total-amnt="<?= $act_req_val; ?>"
                                                                                        data-hotel-acc="<?= $lunch['supplier_id']; ?>"
                                                                                        data-hotel-name="<?= esc($lunch['sup_name']); ?>"
                                                                                        data-type="lunch"
                                                                                        data-activity-id="<?= $lunch['id']; ?>"
                                                                                        total-advance="0"
                                                                                        total-hotel-due="<?= $act_req_val; ?>"
                                                                                        data-total-doller-amt="<?= $act_display; ?>"
                                                                                        <?= $lunch['payment_request'] == 1 ? 'disabled' : '' ?>>
                                                                                        <?= $lunch['payment_request'] == 1 ? 'Requested' : 'Request' ?>
                                                                                    </button>
                                                                                    <?php endif; ?>
                                                                                </div>
                                                                                <?php if ($lunch['payment_request'] == 1 && isset($lunch['requested_amount']) && in_array($lunch['pr_status'], [0, 1, 4])): ?>
                                                                                    <small class="text-success fw-semibold">LKR <?= number_format($lunch['requested_amount'], 2) ?></small>
                                                                                <?php endif; ?>
                                                                            </td>
                                                                        </tr>
                                                                    <?php endforeach; ?>
                                                                <?php endif; ?>

                                                                <!-- New row for agent expenses -->

                                                                <tr class="tour-detail-row" data-tour-id="<?= $tour['tour_id']; ?>">
                                                                    <td colspan="3" class="font-italic text-muted">Driver Allocation</td>
                                                                    <td colspan="11">
                                                                        <?php if (!empty($tour['driverallocationdata'])): ?>
                                                                            <?php foreach ($tour['driverallocationdata'] as $driver): ?>
                                                                                <span class="badge bg-info me-1"><?= $driver['driver_name'] ?? 'N/A'; ?></span>
                                                                            <?php endforeach; ?>
                                                                        <?php else: ?>
                                                                            <span class="badge bg-danger">Driver Not Allocated</span>
                                                                        <?php endif; ?>
                                                                    </td>
                                                                </tr>
                                                                <?php foreach ($tour['advance_payment_requests'] as $advance_request): ?>
                                                                    <tr class="tour-detail-row" data-tour-id="<?= $tour['tour_id']; ?>">
                                                                        <td colspan="3">Advance Request</td>
                                                                        <td><?= $advance_request['Date'] ?? ''; ?></td>
                                                                        <td>Driver :</td>
                                                                        <td colspan="4"> <?= $advance_request['driver_name'] ?? ''; ?></td>
                                                                        <td colspan="4">Request by :- <?= $advance_request['username'] ?? ''; ?></td>
                                                                        <td>
                                                                             <label>LKR &nbsp;</label>
                                                                            <input type="text" class="form-control input-sm request_amount" placeholder="Enter amount" id="input-amount-<?= $advance_request['id']; ?>" value="<?= number_format($advance_request['amount'] ?? 0, 2); ?>" />
                                                                            <?php if (!$hideOperationsButtons): ?>
                                                                            <button class="btn btn-warning btn-sm driver-advance-request"
                                                                                data-request-id="<?= $advance_request['id'] ?? ''; ?>"
                                                                                data-amount="<?= $advance_request['amount'] ?? 0; ?>"
                                                                                data-tour-id="<?= $tour['tour_id'] ?? ''; ?>"
                                                                                data-driver="<?= $advance_request['driver'] ?? ''; ?>"
                                                                                data-manual-id="<?= $tour['manual_id'] ?? ''; ?>"
                                                                                data-driver-name="<?= $advance_request['driver_name'] ?? ''; ?>"
                                                                                data-type="Transport Agent"
                                                                                <?php if (($advance_request['status'] ?? 0) == 1) {
                                                                                    echo 'disabled';
                                                                                } else {
                                                                                    echo '';
                                                                                } ?>>
                                                                                <?php if (($advance_request['status'] ?? 0) == 1) {
                                                                                    echo 'Requested';
                                                                                } else {
                                                                                    echo 'Request';
                                                                                } ?>
                                                                            </button>
                                                                            <?php endif; ?>
                                                                        </td>
                                                                    </tr>
                                                                <?php endforeach; ?>
                                                            <?php else : ?>
                                                                <tr class="tour-detail-row" data-tour-id="<?= $tour['tour_id']; ?>">
                                                                    <td><?= $i; ?></td> <!-- Use $i without incrementing here -->
                                                                    <td><?= $tour['manual_id']; ?></td>
                                                                    <td><?= $tour['consignee']; ?><br><span class="text-muted">(<?= $tour['create_user']; ?>)</span></td>
                                                                    <td><?= date('d M, Y', strtotime($tour['start_date'])); ?></td>
                                                                    <td><?= date('d M, Y', strtotime($tour['end_date'])); ?></td>
                                                                    <td colspan="9" class="font-italic text-muted">No hotels available</td>
                                                                </tr>
                                                                <!-- New row for agent expenses — only for tours flagged transport_only; a
                                                                     tour with hotels simply not yet added/requested isn't a transport-only
                                                                     tour, so it shouldn't get a settlement request box. -->
                                                                <?php if ((int) ($tour['transport_only'] ?? 0) === 1): ?>
                                                                <tr class="tour-detail-row" data-tour-id="<?= $tour['tour_id']; ?>">
                                                                    <td colspan="7" class="font-italic text-muted">Transport Agent - <?= $tour['manual_id']; ?></td>
                                                                    <td>
                                                                        <strong><?= $tour['sup_name']; ?></strong><br>
                                                                        <span class="text-muted"><?= $tour['address']; ?></span>
                                                                    </td>
                                                                    <td><?= $tour['bank_name']; ?><br><?= $tour['branch_name']; ?><br><?= $tour['sup_bank_acc']; ?></td>
                                                                    <td>LKR <?= number_format($tour['total_transport_cost'], 2); ?></td>
                                                                    <td>LKR <?= number_format($tour['advance_dr'], 2); ?></td>
                                                                    <td>LKR <?= number_format($pay, 2); ?></td>
                                                                    <td>
                                                                        <?php if (($pay <= 0) && ($tour['advance_dr'] > 0)) : ?>
                                                                            <button
                                                                                class="btn btn-success btn-sm complete-transport"
                                                                                data-tour-id="<?= $tour['tour_id']; ?>">
                                                                                Complete
                                                                            </button>
                                                                        <?php endif; ?>
                                                                    </td>

                                                                    <td> <input type="text" class="form-control input-sm request_amount" placeholder="Enter amount" id="input-amount-<?= $tour['tour_id']; ?>" value="<?= number_format($pay, 2); ?>" /> <?php if (!$hideOperationsButtons): ?><button class="btn btn-warning btn-sm transport-request" tot-transport="<?= number_format($tour['total_transport_cost'], 2); ?>" tot-advance="<?= number_format($tour['advance_dr'], 2); ?>" tot-due="<?= number_format($pay, 2); ?>" data-tour-id="<?= $tour['tour_id']; ?>" <?php if (empty($hotel) || $hotel['payment_request'] == 1) {
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            echo 'disabled';
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        } else {
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            echo '';
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        } ?>><?php if (empty($hotel) || $hotel['payment_request'] == 1) {
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    echo 'Requested';
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                } else {
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    echo 'Request';
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                } ?></button><?php endif; ?>
                                                                    </td>

                                                                </tr>
                                                                <?php endif; ?>
                                                                <tr class="tour-detail-row" data-tour-id="<?= $tour['tour_id']; ?>">
                                                                    <td colspan="3" class="font-italic text-muted">Driver Allocation</td>
                                                                    <td colspan="11">
                                                                        <?php if (!empty($tour['driverallocationdata'])): ?>
                                                                            <?php foreach ($tour['driverallocationdata'] as $driver): ?>
                                                                                <span class="badge bg-info me-1"><?= $driver['driver_name'] ?? 'N/A'; ?></span>
                                                                            <?php endforeach; ?>
                                                                        <?php else: ?>
                                                                            <span class="badge bg-danger">Driver Not Allocated</span>
                                                                        <?php endif; ?>
                                                                    </td>
                                                                </tr>
                                                                <?php foreach ($tour['advance_payment_requests'] as $advance_request): ?>
                                                                    <tr class="tour-detail-row" data-tour-id="<?= $tour['tour_id']; ?>">
                                                                        <td colspan="3">Advance Request</td>
                                                                        <td><?= $advance_request['Date'] ?? ''; ?></td>
                                                                        <td>Driver :</td>
                                                                        <td colspan="4"> <?= $advance_request['driver_name'] ?? ''; ?></td>
                                                                        <td colspan="4">Request by :- <?= $advance_request['username'] ?? ''; ?></td>
                                                                        <td>
                                                                             <label>LKR &nbsp;</label>
                                                                            <input type="text" class="form-control input-sm request_amount" placeholder="Enter amount" id="input-amount-<?= $advance_request['id']; ?>" value="<?= number_format($advance_request['amount'] ?? 0, 2); ?>" />
                                                                            <?php if (!$hideOperationsButtons): ?>
                                                                            <button class="btn btn-warning btn-sm driver-advance-request"
                                                                                data-request-id="<?= $advance_request['id'] ?? ''; ?>"
                                                                                data-amount="<?= $advance_request['amount'] ?? 0; ?>"
                                                                                data-tour-id="<?= $tour['tour_id'] ?? ''; ?>"
                                                                                data-driver="<?= $advance_request['driver'] ?? ''; ?>"
                                                                                data-manual-id="<?= $tour['manual_id'] ?? ''; ?>"
                                                                                data-driver-name="<?= $advance_request['driver_name'] ?? ''; ?>"
                                                                                data-type="Transport Agent"
                                                                                <?php if (($advance_request['status'] ?? 0) == 1) {
                                                                                    echo 'disabled';
                                                                                } else {
                                                                                    echo '';
                                                                                } ?>>
                                                                                <?php if (($advance_request['status'] ?? 0) == 1) {
                                                                                    echo 'Requested';
                                                                                } else {
                                                                                    echo 'Request';
                                                                                } ?>
                                                                            </button>
                                                                            <?php endif; ?>
                                                                        </td>
                                                                    </tr>
                                                                <?php endforeach; ?>

                                                            <?php endif;
                                                            // endif;
                                                            ?>

                                                            <?php $i++; ?> <!-- Increment $i only once per tour -->

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

               

                </div>
            </div>

            <?= view('layouts/brand') ?>

        </div>
    </div>

    <?= view('layouts/inpage_footer') ?>
    <?= view('layouts/scripts') ?>

    <script src="<?= base_url(); ?>assets/bundles/datatablescripts.bundle.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script>
        function downloadExcel(el) {
            const table = document.getElementById(el);
            const workbook = XLSX.utils.table_to_book(table, {
                raw: true
            });
            XLSX.writeFile(workbook, 'Tour_Details.xlsx');
        }

        // Collapsed by default on load.
        $("#mainTable tbody tr.tour-detail-row").hide();
        $("#mainTable tbody tr.tour-header-row .tour-toggle-icon")
            .removeClass("bi-chevron-down").addClass("bi-chevron-right");

        // Collapse/expand a tour's detail rows when its header row is clicked.
        // Skip clicks on interactive elements inside the header (links/buttons).
        $("#mainTable tbody").on("click", ".tour-header-row", function(e) {
            if ($(e.target).closest("a, button").length) return;
            const tourId = String($(this).data("tour-id"));
            const $icon = $(this).find(".tour-toggle-icon");
            const $detailRows = $("#mainTable tbody tr.tour-detail-row").filter(function() {
                return String($(this).data("tour-id")) === tourId;
            });
            const collapsing = $icon.hasClass("bi-chevron-down");
            $detailRows.toggle(!collapsing);
            $icon.toggleClass("bi-chevron-down", !collapsing).toggleClass("bi-chevron-right", collapsing);
        });

        // Rows are grouped per tour via data-tour-id (hotel/lunch/driver/advance rows
        // don't all repeat the Manual ID / Tour Name text). Match if ANY row in the
        // group matches, then show/hide the whole group together. Collapsed groups
        // stay collapsed after a search is cleared.
        $("#tableSearchInput").on("keyup", function() {
            const term = $(this).val().trim().toLowerCase();
            const headerRows = $("#mainTable tbody tr.tour-header-row");
            const detailRows = $("#mainTable tbody tr.tour-detail-row");

            if (term === "") {
                headerRows.show();
                headerRows.each(function() {
                    const tourId = String($(this).data("tour-id"));
                    const collapsed = $(this).find(".tour-toggle-icon").hasClass("bi-chevron-right");
                    detailRows.filter(function() {
                        return String($(this).data("tour-id")) === tourId;
                    }).toggle(!collapsed);
                });
                return;
            }

            const matchedTourIds = new Set();
            headerRows.add(detailRows).each(function() {
                const tourId = $(this).data("tour-id");
                if (tourId === undefined) return;
                if ($(this).text().toLowerCase().includes(term)) {
                    matchedTourIds.add(String(tourId));
                }
            });

            headerRows.each(function() {
                const tourId = String($(this).data("tour-id"));
                $(this).toggle(matchedTourIds.has(tourId));
            });
            detailRows.each(function() {
                const tourId = String($(this).data("tour-id"));
                $(this).toggle(matchedTourIds.has(tourId));
            });
        });

        $(".complete-button").click(function() {

            const tourId = this.getAttribute('data-tour-id');
            const hotelAcc = this.getAttribute('data-hotel-acc');

            // Show a confirmation dialog using SweetAlert before proceeding
            Swal.fire({
                title: 'Are you sure?',
                text: "You want to complete this payment?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, complete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    // If the user confirms, proceed with the action
                    $(this).prop("disabled", true).text("Processing...").hide(); // Disable and hide the button

                    $.post("<?php echo base_url() ?>tour/tour_accounts/update", {
                        update: "data",
                        tourId: tourId,
                        hotelAcc: hotelAcc,

                    }, function(data) {
                        if (data.status === 200) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Successfully Completed!',
                                showConfirmButton: true
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    location.reload(); // Reload the page on success
                                }
                            });
                        } else if ($.trim(data.status) === 400) {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Error!',
                                text: 'Error occurred.',
                                showConfirmButton: false,
                                timer: 1500
                            });
                        } else {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Oops...',
                                text: 'Something went wrong!',
                                showConfirmButton: false,
                                timer: 1500
                            });
                        }
                    }, "json").always(() => {
                        // Reset the flag or perform any cleanup here if needed
                    });
                }
            });
        });



        $(".request-button").click(function() {

            const tourId = this.getAttribute('data-tour-id');
            const hotelAcc = this.getAttribute('data-hotel-acc');
            const paymentType = $(this).attr("data-type") || '';

            // Hotel requests need today's USD rate set before they can be requested
            if (paymentType === 'Hotel' && $(this).attr("data-rate-missing") === '1') {
                Swal.fire({
                    icon: 'warning',
                    title: 'USD Rate Not Set',
                    text: "Today's USD rate hasn't been entered yet. Please set it before requesting a hotel payment.",
                    showConfirmButton: true,
                    confirmButtonText: 'Set USD Rate',
                    showCancelButton: true,
                    cancelButtonText: 'Close'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = "<?php echo base_url() ?>master_files/daily_usd_rate";
                    }
                });
                return;
            }

            // Find the closest table row
            const row = $(this).closest("tr");
            let requestAmount = row.find(".request_amount").val();
            requestAmount = parseFloat(String(requestAmount).replace(/,/g, ''));

            // Validate the request amount
            if (isNaN(requestAmount) || requestAmount <= 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Amount!',
                    text: 'Please enter a valid amount greater than zero.',
                    showConfirmButton: true
                });
                return; // Exit the function if the amount is invalid
            }

            // For hotel and activity payments, validate that request amount does not exceed payable amount
            if (paymentType === 'Hotel' || paymentType === 'lunch') {
                const payableAmount = parseFloat($(this).attr("data-total-amnt")) || 0;
                const currency = paymentType === 'Hotel' ? '$' : 'LKR ';
                if (payableAmount > 0 && requestAmount > payableAmount) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Amount Exceeded!',
                        text: 'Request amount (' + currency + requestAmount.toFixed(2) + ') cannot exceed the payable amount (' + currency + payableAmount.toFixed(2) + ').',
                        showConfirmButton: true
                    });
                    return;
                }
            }

            // Extract all data from the table row

            const requestData = {
                tourId: $(this).attr("data-tour-id"),
                hotelAcc: $(this).attr("data-hotel-acc"),
                type: $(this).attr("data-type") || '',
                activityId: $(this).attr("data-activity-id") || '',
                manualId: row.find("td:nth-child(2)").text().trim(),
                consignee: row.find("td:nth-child(3)").contents().first().text().trim(),
                createUser: row.find("td:nth-child(3) span").text().replace(/[()]/g, '').trim(),
                startDate: row.find("td:nth-child(4)").text().trim(),
                endDate: row.find("td:nth-child(5)").text().trim(),
                hotelName: $(this).attr("data-hotel-name") || row.find("td:nth-child(6)").text().trim(),
                bankName: row.find("td:nth-child(7)").contents().first().text().trim(),
                branchName: row.find("td:nth-child(7)").contents().eq(2).text().trim(),
                supBankAcc: row.find("td:nth-child(7)").contents().eq(4).text().trim(),
                totalHotelPay: $(this).attr("data-total-amnt"),
                totalHotelAdvance: $(this).attr("total-advance"),
                dueAmount: $(this).attr("total-hotel-due"),
                requestAmount: requestAmount,
                tot_amt_doller: $(this).attr("data-total-doller-amt")
            };


            // Show a confirmation dialog using SweetAlert before proceeding
            Swal.fire({
                title: 'Are you sure?',
                text: "You want to make a payment request?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, Request!'
            }).then((result) => {
                if (result.isConfirmed) {
                    // If the user confirms, proceed with the action
                    $(this).prop("disabled", true).text("Processing...").hide(); // Disable and hide the button

                    $.post("<?php echo base_url() ?>tour/tour_accounts/payment_request", Object.assign({ '<?= csrf_token() ?>': '<?= csrf_hash() ?>' }, requestData), function(data) {   // tf-paydesk-r7e
                        if (data.status === 200) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Successfully Completed!',
                                showConfirmButton: true
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            var isRateMissing = (data.message || '').indexOf("USD rate hasn't been entered") !== -1;
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: data.message,
                                showConfirmButton: isRateMissing,
                                confirmButtonText: 'Set USD Rate',
                                showCancelButton: isRateMissing,
                                cancelButtonText: 'Close',
                                timer: isRateMissing ? undefined : 1500
                            }).then((result) => {
                                if (isRateMissing && result.isConfirmed) {
                                    window.location.href = "<?php echo base_url() ?>master_files/daily_usd_rate";
                                }
                            });
                        }
                    }, "json").fail(function(x) {   // tf-paydesk-r7e: a refused request shows the server's reason
                        Swal.fire({ icon: 'error', title: 'Error!', text: (x && x.responseJSON && x.responseJSON.message) || 'Something went wrong. Please try again.' });
                    });
                }
            });
        });




        $(".complete-transport").click(function() {

            const tourId = this.getAttribute('data-tour-id');


            // Show a confirmation dialog using SweetAlert before proceeding
            Swal.fire({
                title: 'Are you sure?',
                text: "You want to complete this payment?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, complete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    // If the user confirms, proceed with the action
                    $(this).prop("disabled", true).text("Processing...").hide(); // Disable and hide the button

                    $.post("<?php echo base_url() ?>tour/tour_accounts/update_transport", {
                        update: "data",
                        tourId: tourId,

                    }, function(data) {
                        if (data.status === 200) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Successfully Completed!',
                                showConfirmButton: true
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    location.reload(); // Reload the page on success
                                }
                            });
                        } else if ($.trim(data.status) === 400) {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Error!',
                                text: 'Error occurred.',
                                showConfirmButton: false,
                                timer: 1500
                            });
                        } else {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Oops...',
                                text: 'Something went wrong!',
                                showConfirmButton: false,
                                timer: 1500
                            });
                        }
                    }, "json").always(() => {
                        // Reset the flag or perform any cleanup here if needed
                    });
                }
            });
        });


        $(".transport-request").click(function() {
            // Find the closest table row
            const row = $(this).closest("tr");
            let requestAmount = row.find(".request_amount").val();
            requestAmount = parseFloat(requestAmount.replace(/,/g, ''));


            // Extract all data from the table row


            const requestData = {
                tourId: $(this).attr("data-tour-id"),
                hotelAcc: "TRANSPORT", // To differentiate transport requests from hotel requests
                manualId: row.find("td:nth-child(1)").text().trim().replace("Transport Agent - ", ""),
                consignee: row.find("td:nth-child(6) strong").text().trim(), // Remove "Transport Agent - "
                createUser: "Transport Agent",
                startDate: "0000-00-00", // Transport doesn't have a specific start date
                endDate: "0000-00-00", // Transport doesn't have an end date
                hotelName: row.find("td:nth-child(2) strong").text().trim() + " - " + row.find("td:nth-child(2) span").text().trim(), // Send Supplier Name + Address
                bankName: row.find("td:nth-child(3)").contents().first().text().trim(),
                branchName: row.find("td:nth-child(3)").contents().eq(2).text().trim(),
                supBankAcc: row.find("td:nth-child(3)").contents().eq(4).text().trim(),
                totalHotelPay: $(this).attr("tot-transport"),
                totalHotelAdvance: $(this).attr("tot-advance"),
                dueAmount: $(this).attr("tot-due"),
                requestAmount: requestAmount
            };

            // Show a confirmation dialog
            Swal.fire({
                title: 'Are you sure?',
                text: "You want to make a transport payment request?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, Request!'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Disable and hide the button
                    $(this).prop("disabled", true).text("Processing...").hide();

                    $.post("<?php echo base_url() ?>tour/tour_accounts/transport_payment_request", Object.assign({ '<?= csrf_token() ?>': '<?= csrf_hash() ?>' }, requestData), function(data) {   // tf-paydesk-r7f
                        if (data.status === 200) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Successfully Requested!',
                                showConfirmButton: true
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: data.message,
                                showConfirmButton: false,
                                timer: 1500
                            });
                        }
                    }, "json").fail(function (x) {   // tf-paydesk-r7f: a refused request shows the server's reason
                        Swal.fire({ icon: 'error', title: 'Not done', text: (x && x.responseJSON && x.responseJSON.message) || 'Something went wrong. Please try again.' });
                    });
                }
            });
        });

        $(".driver-advance-request").click(function() {
            const row = $(this).closest("tr");
            let requestAmount = row.find(".request_amount").val();
            let requestId = $(this).attr("data-request-id");
            let tourId = $(this).attr("data-tour-id");
            let driver = $(this).attr("data-driver");
            let manualId = $(this).attr("data-manual-id");
            let drivername = $(this).attr("data-driver-name");
            let paytype =  $(this).attr("data-type");
            requestAmount = parseFloat(String(requestAmount).replace(/,/g, ''));

            // Validate amount
            if (isNaN(requestAmount) || requestAmount <= 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Amount!',
                    text: 'Please enter a valid amount greater than zero.',
                    showConfirmButton: true
                });
                return;
            }

            const maxAmount = parseFloat($(this).attr("data-amount")) || 0;
            if (maxAmount > 0 && requestAmount > maxAmount) {
                Swal.fire({
                    icon: 'error',
                    title: 'Amount Exceeded!',
                    text: 'Request amount (LKR ' + requestAmount.toFixed(2) + ') cannot exceed the advance amount (LKR ' + maxAmount.toFixed(2) + ').',
                    showConfirmButton: true
                });
                return;
            }



            const requestData = {
                requestId: requestId,
                tourId: tourId,
                hotelAcc: driver,
                manualId: manualId,
                consignee: drivername,
                createUser: "Transport Agent",
                startDate: "0000-00-00",
                endDate: "0000-00-00",
                hotelName: '',
                bankName: '',
                branchName: '',
                supBankAcc: '',
                totalHotelPay: $(this).attr("tot-transport"),
                totalHotelAdvance: requestAmount,
                dueAmount: $(this).attr("tot-due"),
                requestAmount: requestAmount,
                type: paytype
            };

            // alert(JSON.stringify(requestData));

            // Show a confirmation dialog
            Swal.fire({
                title: 'Are you sure?',
                text: "You want to make a driver advance payment request?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, Request!'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Disable and hide the button
                    $(this).prop("disabled", true).text("Processing...");

                    $.post("<?php echo base_url() ?>tour/tour_accounts/driver_advance_payment_request", Object.assign({ '<?= csrf_token() ?>': '<?= csrf_hash() ?>' }, requestData), function(data) {   // tf-paydesk-r7e
                        if (data.status === 200) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Successfully Requested!',
                                showConfirmButton: true
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: data.message,
                                showConfirmButton: false,
                                timer: 1500
                            });
                        }
                    }, "json").fail(function(x) {   // tf-paydesk-r7e: a refused request shows the server's reason
                        Swal.fire({ icon: 'error', title: 'Error!', text: (x && x.responseJSON && x.responseJSON.message) || 'Something went wrong. Please try again.' });
                    });
                }
            });
        });

        // Handle invoice acceptance
        $(".inv-accept-button").click(function() {
            const tourId = this.getAttribute('data-tour-id');
            const manualId = this.getAttribute('data-manual-id');
            const invNo = this.getAttribute('data-inv');

            // Show a confirmation dialog using SweetAlert before proceeding
            Swal.fire({
                title: 'Are you sure?',
                text: "You want to accept this invoice?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, accept it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    // If the user confirms, proceed with the action
                    $(this).prop("disabled", true).text("Processing...").hide(); // Disable and hide the button

                    $.post("<?php echo base_url() ?>tour/tour_accounts/accept_invoice", {
                        tourId: tourId,
                        manualId: manualId,
                        invNo: invNo
                    }, function(data) {
                        if (data.status === 200) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Invoice Accepted Successfully!',
                                showConfirmButton: true
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    location.reload(); // Reload the page on success
                                }
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: data.message,
                                showConfirmButton: false,
                                timer: 1500
                            });
                        }
                    }, "json");
                }
            });
        });

        $(".inv-reject-button").click(function() {
            const tourId = this.getAttribute('data-tour-id');
            const manualId = this.getAttribute('data-manual-id');
            const invNo = this.getAttribute('data-inv');

            // Show a confirmation dialog using SweetAlert before proceeding
            Swal.fire({
                title: 'Are you sure?',
                text: "You want to reject this invoice?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, reject it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    // If the user confirms, proceed with the action
                    $(this).prop("disabled", true).text("Processing...").hide(); // Disable and hide the button

                    $.post("<?php echo base_url() ?>tour/tour_accounts/reject_invoice", {
                        tourId: tourId,
                        manualId: manualId,
                        invNo: invNo
                    }, function(data) {
                        if (data.status === 200) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Invoice Rejected Successfully!',
                                showConfirmButton: true
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    location.reload(); // Reload the page on success
                                }
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: data.message,
                                showConfirmButton: false,
                                timer: 1500
                            });
                        }
                    }, "json");
                }
            });
        });
    </script>

</body>

</html>