<?= view('layouts/head') ?>

<body>

    <style>
        @media print {
            @page {
                size: A4 landscape;
                margin: 20mm;
            }

            body {
                margin: 0;
                padding: 0;
            }

            .no-print {
                display: none !important;
            }

            .main-content {
                width: 100%;
            }
        }

        .table-custom tr {
            height: 2% !important;
        }

        .table th,
        .table td {
            padding: 1px !important;
            margin: 0;
        }
    </style>

    <?= view('layouts/topbar', ['topbar_back' => 'close', 'topbar_home' => false, 'topbar_logout' => false]) ?>

    <div class="container-fluid p-4 mt-5">
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <h2 class="p-title" style="text-align: center;">Shop Allocation</h2>
                </div>
            </div>
        </div>

        <div class="row clearfix mt-3 justify-content-center">
            <div class="col-md-8">
                <div class="card p-4">
                    <div class="body">
                        <div class="row">
                            <?php foreach ($tour_main_details as $detail) { ?>

                                <div class="col-md-2">
                                    <div class="d-flex flex-column">
                                        <span class="text-muted">Tour Id</span>
                                        <input type="hidden" name="tour_id" id="tour_id" value="<?php echo $tour_id ?>">
                                        <h6 class="fw-bold" id="t_id"><?php echo $tour_id ?></h6>
                                        <input type="hidden" name="is_settled" id="is_settled" value="<?php echo $detail['transport_settled'] ?>">
                                    </div>
                                    <div class="d-flex flex-column">
                                        <span class="text-muted">Manual Id</span>
                                        <input type="hidden" name="manual_id" id="manual_id" value="<?php echo $detail['manual_id'] ?>">
                                        <h6 class="fw-bold" id="t_manual_id"><?php echo $detail['manual_id'] ?></h6>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="d-flex flex-column">
                                        <span class="text-muted">Tour Name</span>
                                        <h6 class="fw-bold" id="t_consignee"><?php echo $detail['consignee'] ?></h6>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="d-flex flex-column">
                                        <span class="text-muted">Tour Agent</span>
                                        <h6 class="fw-bold" id="t_agent_name"><?php echo $detail['agent_name'] ?></h6>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="d-flex flex-column">
                                        <span class="text-muted">Transport Agent</span>
                                        <span id="t_transportagent" class="d-none"><?php echo $detail['transport_agent'] ?></span>
                                        <h6 class="fw-bold" id="t_transportagent_name">
                                            <?php echo $detail['transport_agent_name'] ?>
                                        </h6>
                                    </div>
                                    <div class="d-flex flex-column">
                                        <span class="text-muted">Guide</span>
                                        <span id="t_guide" class="d-none"><?php echo $detail['guide_name'] ?></span>
                                        <h6 class="fw-bold" id="guide_name">
                                            <?php echo $detail['guide_name'] ?>
                                        </h6>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="d-flex flex-column">
                                        <span class="text-muted">Passengers</span>
                                        <h6 class="fw-bold" id="t_passenger_count"><?php echo $detail['passenger_count'] ?></h6>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="d-flex flex-column">
                                        <span class="text-muted">Start Date</span>
                                        <h6 class="fw-bold" id="t_start_date"><?php echo $detail['start_date'] ?></h6>
                                    </div>
                                    <div class="d-flex flex-column">
                                        <span class="text-muted">End Date</span>
                                        <h6 class="fw-bold" id="t_end_date"><?php echo $detail['end_date'] ?></h6>
                                    </div>
                                </div>
                                <input type="hidden" name="finalize_txt" id="finalize_txt" value="<?php echo $detail['finalize_txt'] ?>">
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4 justify-content-center">
        <div class="col-xxl-10">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary bg-gradient text-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-calendar3 me-2"></i>Tour Itinerary & Shop Allocation</h5>
                        <span class="badge bg-light text-primary"><?= count($tour_details) ?> Days</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center" style="width: 60px;">Day</th>
                                    <th style="width: 120px;">Date</th>
                                    <th>Hotel / Category</th>
                                    <th class="text-center" style="width: 60px;">Meal</th>
                                    <th>Visits / Sightseeing</th>
                                    <th>Allocated Shops</th>
                                    <th class="text-center" style="width: 100px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $i = 1;
                                foreach ($tour_details as $row) {
                                    $meal = $row['meal_code'];
                                    $mealBadge = '';
                                    if ($meal == 'fb') {
                                        $mealBadge = '<span class="badge bg-success">FB</span>';
                                    } else if ($meal == 'hb') {
                                        $mealBadge = '<span class="badge bg-info">HB</span>';
                                    } else if ($meal == 'bb') {
                                        $mealBadge = '<span class="badge bg-warning text-dark">BB</span>';
                                    }

                                    // Get locations for this date
                                    $loc = array();
                                    foreach ($sub_locations as $row1) {
                                        if ($row1['date_id'] == $row['tour_date']) {
                                            $loc[] = $row1['from'] . ' - ' . $row1['to'];
                                        }
                                    }
                                ?>
                                <tr>
                                    <td class="text-center">
                                        <span class="badge bg-primary rounded-pill" style="width: 30px;"><?= $i++ ?></span>
                                    </td>
                                    <td>
                                        <div class="fw-semibold"><?= date('D, M d', strtotime($row['tour_date'])) ?></div>
                                        <small class="text-muted"><?= $row['tour_date'] ?></small>
                                    </td>
                                    <td>
                                        <div class="fw-semibold"><?= $row['hotel_name'] ?: '<span class="text-muted fst-italic">-</span>' ?></div>
                                        <?php if (!empty($row['rtype'])) { ?>
                                            <small class="text-success"><?= $row['rtype'] ?></small>
                                        <?php } ?>
                                    </td>
                                    <td class="text-center"><?= $mealBadge ?></td>
                                    <td>
                                        <?php if (!empty($loc)) { ?>
                                            <div class="d-flex flex-wrap gap-1">
                                                <?php foreach ($loc as $location) { ?>
                                                    <span class="badge bg-light text-dark border"><?= $location ?></span>
                                                <?php } ?>
                                            </div>
                                        <?php } else { ?>
                                            <span class="text-muted">-</span>
                                        <?php } ?>
                                    </td>
                                    <td>
                                        <div id="selected-shops-<?= $row['tour_date'] ?>"></div>
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-outline-primary btn-sm" onclick="openShopModal('<?= $row['tour_date'] ?>')">
                                            <i class="bi bi-shop"></i> Add
                                        </button>
                                    </td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>


    </div>

    <!-- Shop Selection Modal -->
    <div class="modal fade" id="shopModal" tabindex="-1" aria-labelledby="shopModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="shopModalLabel">Select Shops</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="shopSelect" class="form-label">Select Shops</label>
                        <select class="form-control" id="shopSelect" multiple="multiple" style="width: 100%;">
                            <!-- Options will be loaded dynamically -->
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveSelectedShops()">Save Shops</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

    <?= view('layouts/theme_settings') ?>
    <?= view('layouts/scripts') ?>

    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        let currentDate = null;
        let selectedShops = {};
        let allShops = [];

        // Initialize Select2 when modal is shown
        $('#shopModal').on('shown.bs.modal', function() {
            $('#shopSelect').select2({
                theme: 'bootstrap-5',
                placeholder: 'Search and select shops...',
                allowClear: true,
                dropdownParent: $('#shopModal'),
                templateResult: formatShop,
                templateSelection: formatShopSelection
            });
        });

        // Format shop option with name and address
        function formatShop(shop) {
            if (!shop.id) {
                return shop.text;
            }
            let address = $(shop.element).data('address') || '';
            let $shop = $(
                '<div><strong>' + shop.text + '</strong>' +
                (address ? '<br><small class="text-muted">' + address + '</small>' : '') +
                '</div>'
            );
            return $shop;
        }

        // Format selected shop
        function formatShopSelection(shop) {
            return shop.text;
        }

        // Open shop modal
        function openShopModal(date) {
            currentDate = date;
            loadShops();
            $('#shopModal').modal('show');
        }

        // Load registered shops
        function loadShops() {
            $.ajax({
                url: '<?= base_url('dashboard2/transport/shop_allow/get_shops') ?>',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.status === 200) {
                        allShops = response.shops;
                        populateSelect2(response.shops);
                    } else {
                        toastr.error('Failed to load shops');
                    }
                },
                error: function() {
                    toastr.error('Error loading shops');
                }
            });
        }

        // Populate Select2 with shops
        function populateSelect2(shops) {
            let $select = $('#shopSelect');
            $select.empty();

            shops.forEach(function(shop) {
                let option = new Option(shop.Name, shop.ID, false, false);
                $(option).data('address', shop.Address || '');
                $(option).data('sup-acc-no', shop.Sup_Acc_No || '');
                $select.append(option);
            });

            // Set previously selected values for current date
            if (selectedShops[currentDate]) {
                $select.val(selectedShops[currentDate]).trigger('change');
            } else {
                $select.val(null).trigger('change');
            }
        }

        // Save selected shops
        function saveSelectedShops() {
            let selected = $('#shopSelect').val() || [];

            if (selected.length === 0) {
                toastr.warning('Please select at least one shop');
                return;
            }

            // Get both values and text for selected shops
            let shopData = [];
            $('#shopSelect option:selected').each(function() {
                shopData.push({
                    id: $(this).val(),
                    name: $(this).text()
                });
            });

            selectedShops[currentDate] = selected;

            $.ajax({
                url: '<?= base_url('dashboard2/transport/shop_allow/save_shops') ?>',
                type: 'POST',
                data: {
                    tour_id: $('#tour_id').val(),
                    manual_id: $('#manual_id').val(),
                    tour_date: currentDate,
                    shop_ids: JSON.stringify(shopData)
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 200) {
                        toastr.success('Shops saved successfully');
                        // $('#shopModal').modal('hide');
                        // displaySelectedShops(currentDate, response.shops);
                        location.reload();
                    } else {
                        toastr.error(response.message || 'Failed to save shops');
                    }
                },
                error: function() {
                    toastr.error('Error saving shops');
                }
            });
        }

        // Display selected shops for a date
        function displaySelectedShops(date, shops) {
            let html = '<div class="d-flex flex-column gap-1">';
            shops.forEach(function(shop, index) {
                html += `<div class="d-flex align-items-center p-2 bg-light rounded border-start border-success border-3">
                            <i class="bi bi-shop text-success me-2"></i>
                            <span class="fw-medium">${shop.shop_name}</span>
                        </div>`;
            });
            html += '</div>';
            $('#selected-shops-' + date).html(html);
        }

        // Load existing shop allocations on page load
        $(document).ready(function() {
            loadExistingShopAllocations();
        });

        function loadExistingShopAllocations() {
            $.ajax({
                url: '<?= base_url('dashboard2/transport/shop_allow/get_allocated_shops') ?>',
                type: 'GET',
                data: {
                    tour_id: $('#tour_id').val()
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 200 && response.data) {
                        Object.keys(response.data).forEach(function(date) {
                            selectedShops[date] = response.data[date].map(shop => shop.shop_id);
                            displaySelectedShops(date, response.data[date]);
                        });
                    }
                }
            });
        }
    </script>

</body>




</html>