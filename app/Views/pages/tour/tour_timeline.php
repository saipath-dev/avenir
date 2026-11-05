<?= view('layouts/head') ?>

<body>

    <!-- Begin page -->
    <div id="layout-wrapper">

        <?= view('layouts/header') ?>
        <?= view('layouts/sidebar') ?>

        <!-- Start right Content here -->
        <div class="main-content">

            <div class="page-content">
                <div class="container-fluid">

                    <style>
                        /* Step number badge */
                        .step-badge {
                            display: inline-flex;
                            align-items: center;
                            justify-content: center;
                            width: 36px;
                            height: 36px;
                            border-radius: 50%;
                            font-weight: bold;
                            font-size: 16px;
                            margin-right: 12px;
                            flex-shrink: 0;
                        }

                        .step-badge.status-completed {
                            background-color: #10b981;
                            color: white;
                        }

                        .step-badge.status-in_progress {
                            background-color: #f59e0b;
                            color: white;
                        }

                        .step-badge.status-not_started {
                            background-color: #9ca3af;
                            color: white;
                        }

                        /* Step icon styling */
                        .step-icon {
                            font-size: 28px;
                            margin-right: 12px;
                            flex-shrink: 0;
                        }

                        .step-icon.status-completed {
                            color: #10b981;
                        }

                        .step-icon.status-in_progress {
                            color: #f59e0b;
                        }

                        .step-icon.status-not_started {
                            color: #9ca3af;
                        }

                        /* Accordion button background colors */
                        .accordion-button.status-completed:not(.collapsed) {
                            background-color: #d1fae5;
                            color: #065f46;
                        }

                        .accordion-button.status-in_progress:not(.collapsed) {
                            background-color: #fef3c7;
                            color: #92400e;
                        }

                        .accordion-button.status-not_started:not(.collapsed) {
                            background-color: #f3f4f6;
                            color: #374151;
                        }

                        /* Status badge in header */
                        .status-badge {
                            display: inline-block;
                            padding: 4px 12px;
                            border-radius: 12px;
                            font-size: 12px;
                            font-weight: 600;
                            margin-left: 8px;
                        }

                        .status-badge.completed {
                            background-color: #10b981;
                            color: white;
                        }

                        .status-badge.in_progress {
                            background-color: #f59e0b;
                            color: white;
                        }

                        .status-badge.not_started {
                            background-color: #9ca3af;
                            color: white;
                        }

                        /* Accordion header content layout */
                        .accordion-step-header {
                            display: flex;
                            align-items: center;
                            width: 100%;
                        }

                        .accordion-step-title {
                            flex: 1;
                            font-size: 18px;
                            font-weight: 600;
                            margin: 0;
                        }

                        .accordion-step-summary {
                            font-size: 13px;
                            color: #6b7280;
                            margin-top: 4px;
                        }

                        /* Stage branches list styling */
                        .stage-branches-list {
                            list-style: none;
                            padding: 0;
                            margin: 0 0 20px 0;
                        }

                        .stage-branches-list li {
                            padding: 10px 0;
                            border-bottom: 1px solid #e5e7eb;
                            display: flex;
                            align-items: flex-start;
                        }

                        .stage-branches-list li:last-child {
                            border-bottom: none;
                        }

                        .stage-branches-list .branch-icon {
                            font-size: 18px;
                            margin-right: 12px;
                            flex-shrink: 0;
                            margin-top: 2px;
                        }

                        .stage-branches-list .branch-icon.completed {
                            color: #10b981;
                        }

                        .stage-branches-list .branch-icon.pending {
                            color: #9ca3af;
                        }

                        .stage-branches-list .branch-content {
                            flex: 1;
                        }

                        .stage-branches-list .branch-title {
                            font-weight: 600;
                            color: #1f2937;
                            margin-bottom: 2px;
                        }

                        .stage-branches-list .branch-details {
                            font-size: 13px;
                            color: #6b7280;
                        }

                        /* Hotels table styling */
                        .hotels-table-wrapper {
                            margin-top: 15px;
                        }

                        .hotels-table-wrapper h6 {
                            font-weight: 600;
                            margin-bottom: 10px;
                            color: #374151;
                        }

                        /* Mobile responsive adjustments */
                        @media (max-width: 768px) {
                            .accordion-step-title {
                                font-size: 16px;
                            }

                            .step-badge {
                                width: 30px;
                                height: 30px;
                                font-size: 14px;
                                margin-right: 8px;
                            }

                            .step-icon {
                                font-size: 24px;
                                margin-right: 8px;
                            }

                            .table-responsive {
                                font-size: 0.875rem;
                            }

                            .status-badge {
                                display: block;
                                margin-left: 0;
                                margin-top: 8px;
                                width: fit-content;
                            }
                        }

                        /* Accordion customization */
                        .accordion-item {
                            margin-bottom: 12px;
                            border: 1px solid #e5e7eb;
                            border-radius: 8px;
                            overflow: hidden;
                        }

                        .accordion-button {
                            padding: 16px 20px;
                            font-weight: 500;
                        }

                        .accordion-button:focus {
                            box-shadow: none;
                            border-color: #e5e7eb;
                        }

                        .accordion-body {
                            padding: 20px;
                            background-color: #fafafa;
                        }

                        /* Details list styling */
                        .details-list {
                            margin-bottom: 15px;
                        }

                        .details-list .detail-item {
                            padding: 8px 0;
                            font-size: 14px;
                        }

                        .details-list .detail-label {
                            font-weight: 600;
                            color: #374151;
                            margin-right: 8px;
                        }

                        .details-list .detail-value {
                            color: #6b7280;
                        }
                    </style>

                    <!-- Header Section -->
                    <div class="bg-white rounded shadow-sm p-4 mb-4 border-top border-primary border-3">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                            <div>
                                <h4 class="mb-2 fw-bold">Tour Timeline: <?= esc($tour_info->manual_id) ?></h4>
                                <p class="text-muted mb-1"><?= esc($tour_info->tour_name) ?></p>
                                <?php if ($tour_info->start_date && $tour_info->end_date): ?>
                                <p class="text-muted mb-0 small">
                                    <i class="bi bi-calendar-event"></i>
                                    <?= date('d M Y', strtotime($tour_info->start_date)) ?> -
                                    <?= date('d M Y', strtotime($tour_info->end_date)) ?>
                                </p>
                                <?php endif; ?>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="<?= base_url('tour/quotation/timeline/' . $tour_info->manual_id) ?>"
                                   class="btn btn-primary btn-sm">
                                    <i class="bi bi-eye"></i> View Details
                                </a>
                                <a href="<?= base_url('tour/quotation') ?>"
                                   class="btn btn-secondary btn-sm">
                                    <i class="bi bi-arrow-left"></i> Back
                                </a>
                            </div>
                        </div>

                        <!-- Progress Summary -->
                        <div class="row g-3 mt-3">
                            <?php
                            $completed = count(array_filter($steps, fn($s) => $s['status'] === 'completed'));
                            $in_progress = count(array_filter($steps, fn($s) => $s['status'] === 'in_progress'));
                            $not_started = count(array_filter($steps, fn($s) => $s['status'] === 'not_started'));
                            ?>
                            <div class="col-md-4">
                                <div class="border border-success rounded p-3 bg-success bg-opacity-10">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-check-circle-fill text-success fs-1 me-3"></i>
                                        <div>
                                            <p class="text-muted mb-0 small">Completed</p>
                                            <h4 class="mb-0 text-success fw-bold"><?= $completed ?></h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="border border-warning rounded p-3 bg-warning bg-opacity-10">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-hourglass-split text-warning fs-1 me-3"></i>
                                        <div>
                                            <p class="text-muted mb-0 small">In Progress</p>
                                            <h4 class="mb-0 text-warning fw-bold"><?= $in_progress ?></h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="border border-secondary rounded p-3 bg-secondary bg-opacity-10">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-circle text-secondary fs-1 me-3"></i>
                                        <div>
                                            <p class="text-muted mb-0 small">Not Started</p>
                                            <h4 class="mb-0 text-secondary fw-bold"><?= $not_started ?></h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Accordion Timeline -->
                    <div class="accordion" id="tourTimeline">
                        <?php foreach ($steps as $index => $step): ?>
                        <div class="accordion-item">
                            <!-- Accordion Header -->
                            <h2 class="accordion-header" id="heading<?= $step['step_number'] ?>">
                                <button class="accordion-button status-<?= $step['status'] ?> <?= $index === 0 ? '' : 'collapsed' ?>"
                                        type="button"
                                        data-bs-toggle="collapse"
                                        data-bs-target="#collapse<?= $step['step_number'] ?>"
                                        aria-expanded="<?= $index === 0 ? 'true' : 'false' ?>"
                                        aria-controls="collapse<?= $step['step_number'] ?>">
                                    <div class="accordion-step-header">
                                        <!-- Step Badge -->
                                        <span class="step-badge status-<?= $step['status'] ?>">
                                            <?= $step['step_number'] ?>
                                        </span>

                                        <!-- Step Icon -->
                                        <i class="step-icon bi bi-<?= $step['icon_class'] ?> status-<?= $step['status'] ?>"></i>

                                        <!-- Step Title and Summary -->
                                        <div class="flex-grow-1">
                                            <div class="accordion-step-title">
                                                <?= esc($step['name']) ?>
                                                <span class="status-badge <?= $step['status'] ?>">
                                                    <?= ucwords(str_replace('_', ' ', $step['status'])) ?>
                                                </span>
                                            </div>

                                            <!-- Quick Summary in Header -->
                                            <?php if (!empty($step['main_data']['value']) && $step['main_data']['value'] != 'Not started' && $step['main_data']['value'] != 'Not generated'): ?>
                                            <div class="accordion-step-summary">
                                                <?= esc($step['main_data']['label']) ?>: <?= esc($step['main_data']['value']) ?>
                                                <?php if (!empty($step['main_data']['timestamp'])): ?>
                                                on <?= date('d M Y, h:i A', strtotime($step['main_data']['timestamp'])) ?>
                                                <?php endif; ?>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </button>
                            </h2>

                            <!-- Accordion Body -->
                            <div id="collapse<?= $step['step_number'] ?>"
                                 class="accordion-collapse collapse <?= $index === 0 ? 'show' : '' ?>"
                                 aria-labelledby="heading<?= $step['step_number'] ?>"
                                 data-bs-parent="#tourTimeline">
                                <div class="accordion-body">

                                    <!-- Stage Branches (for steps 3 and 4) -->
                                    <?php if (($step['step_number'] == 3 || $step['step_number'] == 4) && !empty($step['stage_branches'])): ?>
                                    <div class="mb-4">
                                        <h6 class="fw-bold mb-3">
                                            <i class="bi bi-diagram-3 me-2"></i>Progress Stages
                                        </h6>
                                        <ul class="stage-branches-list">
                                            <?php foreach ($step['stage_branches'] as $branch): ?>
                                            <li>
                                                <i class="branch-icon bi bi-<?= $branch['completed'] ? 'check-circle-fill completed' : 'circle pending' ?>"></i>
                                                <div class="branch-content">
                                                    <div class="branch-title"><?= esc($branch['title']) ?> By:</div>
                                                    <div class="branch-details">
                                                        <?php if ($branch['completed']): ?>
                                                            <?= esc($branch['user']) ?>
                                                            <?php if (!empty($branch['timestamp'])): ?>
                                                            <br><small class="text-muted"><?= date('d M Y, h:i A', strtotime($branch['timestamp'])) ?></small>
                                                            <?php endif; ?>
                                                        <?php else: ?>
                                                            <span class="text-muted">Pending</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                    <?php endif; ?>

                                    <!-- Hotels Table (for step 3) -->
                                    <?php if (!empty($step['hotels']) && $step['step_number'] == 3): ?>
                                    <div class="hotels-table-wrapper">
                                        <h6 class="fw-bold mb-3">
                                            <i class="bi bi-building me-2"></i>Hotels Status
                                        </h6>
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-sm table-hover">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Hotel Name</th>
                                                        <th class="text-center">Confirmed</th>
                                                        <th class="text-center">Payment</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($step['hotels'] as $hotel): ?>
                                                    <tr>
                                                        <td class="fw-semibold"><?= esc($hotel->hotel_name) ?></td>
                                                        <td class="text-center">
                                                            <?php if ($hotel->booking_confirmed_flag == 1): ?>
                                                                <i class="bi bi-check-circle-fill text-success fs-5"></i>
                                                            <?php else: ?>
                                                                <i class="bi bi-x-circle-fill text-danger fs-5"></i>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td class="text-center">
                                                            <?php if ($hotel->hotel_payment_flag == 1): ?>
                                                                <i class="bi bi-check-circle-fill text-success fs-5"></i>
                                                            <?php else: ?>
                                                                <i class="bi bi-x-circle-fill text-danger fs-5"></i>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                    <!-- Additional Details (sub_data) -->
                                    <?php if (!empty($step['sub_data'])): ?>
                                    <div class="details-list">
                                        <h6 class="fw-bold mb-3">
                                            <i class="bi bi-info-circle me-2"></i>Details
                                        </h6>
                                        <?php foreach ($step['sub_data'] as $data): ?>
                                        <div class="detail-item">
                                            <span class="detail-label"><?= esc($data['label']) ?>:</span>
                                            <span class="detail-value"><?= esc($data['value']) ?></span>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php endif; ?>

                                    <!-- Empty State -->
                                    <?php if (empty($step['sub_data']) && empty($step['hotels']) && empty($step['stage_branches'])): ?>
                                    <div class="text-center py-4 text-muted">
                                        <i class="bi bi-hourglass fs-1 d-block mb-2"></i>
                                        <p class="mb-0">Awaiting action</p>
                                    </div>
                                    <?php endif; ?>

                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Footer Info -->
                    <div class="alert alert-info mt-4 d-flex align-items-start">
                        <i class="bi bi-info-circle fs-5 me-3 mt-1"></i>
                        <div>
                            <strong>Note:</strong> This timeline shows the complete workflow progress for tour
                            <strong><?= esc($tour_info->manual_id) ?></strong>.
                            Data is fetched in real-time from the database and reflects the current status of each workflow step.
                        </div>
                    </div>

                </div>
                <!-- container-fluid -->
            </div>
            <!-- End Page-content -->

            <?= view('layouts/inpage_footer') ?>

        </div>
        <!-- end main content-->

    </div>
    <!-- END layout-wrapper -->

    <?= view('layouts/theme_settings') ?>

    <?= view('layouts/scripts') ?>

</body>

</html>
