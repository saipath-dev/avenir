<?= view('layouts/head') ?>

<body>

    <style>
        .date-card {
            border-left: 4px solid #405189;
            transition: all 0.3s;
        }

        .date-card:hover {
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .shop-item {
            background: #f8f9fa;
            border-radius: 4px;
            padding: 10px;
            margin-bottom: 8px;
            border-left: 3px solid #78b848;
        }

        .shop-item:hover {
            background: #e9ecef;
        }

        .add-shop-btn {
            border: 2px dashed #405189;
            background: transparent;
            color: #405189;
            transition: all 0.3s;
        }

        .add-shop-btn:hover {
            background: #405189;
            color: white;
        }

        .badge-date {
            font-size: 0.85rem;
            padding: 6px 12px;
        }
    </style>

    <?= view('layouts/topbar', ['topbar_back' => 'close', 'topbar_home' => false, 'topbar_logout' => false]) ?>

    <div class="container-fluid p-4 mt-5">
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <h2 class="p-title" style="text-align: center;">Shop Allocation - Date Wise</h2>
                </div>
            </div>
        </div>

        <!-- Tour Details Section -->
        <div class="row clearfix mt-3 justify-content-center">
            <div class="col-md-10">
                <div class="card p-4">
                    <div class="body">
                        <div class="row">
                            <?php foreach ($tour_main_details as $detail) { ?>
                                <div class="col-md-2">
                                    <div class="d-flex flex-column mb-3">
                                        <span class="text-muted">Tour Id</span>
                                        <input type="hidden" name="tour_id" id="tour_id" value="<?php echo $tour_id ?>">
                                        <h6 class="fw-bold" id="t_id"><?php echo $tour_id ?></h6>
                                    </div>
                                    <div class="d-flex flex-column">
                                        <span class="text-muted">Manual Id</span>
                                        <h6 class="fw-bold"><?php echo $detail['manual_id'] ?></h6>
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
                                        <h6 class="fw-bold"><?php echo $detail['agent_name'] ?></h6>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="d-flex flex-column">
                                        <span class="text-muted">Passengers</span>
                                        <h6 class="fw-bold"><?php echo $detail['passenger_count'] ?></h6>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="d-flex flex-column">
                                        <span class="text-muted">Start Date</span>
                                        <h6 class="fw-bold"><?php echo $detail['start_date'] ?></h6>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="d-flex flex-column">
                                        <span class="text-muted">End Date</span>
                                        <h6 class="fw-bold"><?php echo $detail['end_date'] ?></h6>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Date-wise Shop Allocation Section -->
        <div class="row mt-4 justify-content-center">
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Shop Allocation by Date</h5>
                    </div>
                    <div class="card-body">
                        <div id="date-wise-shops-container">
                            <!-- Date-wise shop allocation will be loaded here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Modal for Adding Shop -->
    <div class="modal fade" id="addShopModal" tabindex="-1" aria-labelledby="addShopModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addShopModalLabel">Add Shop</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addShopForm">
                        <input type="hidden" id="allocation_date" name="allocation_date">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="shop_name" class="form-label">Shop Name <span class="text-danger">*</span></label>
                                <select class="form-control js-example-basic-single" id="shop_name" name="shop_name" required>
                                    <option value="">Select Shop</option>
                                    <?php if (isset($shops) && is_array($shops)) : ?>
                                        <?php foreach ($shops as $shop) : ?>
                                            <option value="<?php echo $shop['id'] ?>"><?php echo $shop['shop_name'] ?></option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="shop_type" class="form-label">Shop Type</label>
                                <select class="form-control" id="shop_type" name="shop_type">
                                    <option value="">Select Type</option>
                                    <option value="Gem">Gem</option>
                                    <option value="Textile">Textile</option>
                                    <option value="Spice">Spice</option>
                                    <option value="Tea">Tea</option>
                                    <option value="Handicraft">Handicraft</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="arrival_time" class="form-label">Arrival Time</label>
                                <input type="time" class="form-control" id="arrival_time" name="arrival_time">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="departure_time" class="form-label">Departure Time</label>
                                <input type="time" class="form-control" id="departure_time" name="departure_time">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="shop_notes" class="form-label">Notes</label>
                                <textarea class="form-control" id="shop_notes" name="shop_notes" rows="3"></textarea>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveShopBtn">Save Shop</button>
                </div>
            </div>
        </div>
    </div>

    <?= view('layouts/theme_settings') ?>
    <?= view('layouts/scripts') ?>

    <script>
        $('.js-example-basic-single').select2({
            placeholder: "Select",
            allowClear: true,
            dropdownParent: $('#addShopModal')
        });

        $(document).ready(function() {
            var tour_id = $("#tour_id").val();
            loadDateWiseShops(tour_id);
        });

        // Load date-wise shops
        function loadDateWiseShops(tour_id) {
            $.ajax({
                url: '<?php echo base_url('dashboard2/transport/get_datewise_shops') ?>',
                type: 'GET',
                data: {
                    tour_id: tour_id
                },
                dataType: 'json',
                success: function(data) {
                    if (data.status == 200) {
                        renderDateWiseShops(data.data);
                    } else {
                        console.error(data.message || 'Failed to load data');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error loading shop allocations:', error);
                }
            });
        }

        // Render date-wise shops
        function renderDateWiseShops(data) {
            var container = $('#date-wise-shops-container');
            container.empty();

            if (!data.dates || data.dates.length === 0) {
                container.html('<div class="alert alert-info">No dates available for this tour.</div>');
                return;
            }

            var html = '';
            data.dates.forEach(function(dateInfo) {
                html += '<div class="date-card card mb-3">';
                html += '  <div class="card-header d-flex justify-content-between align-items-center">';
                html += '    <div>';
                html += '      <h6 class="mb-0"><i class="bi bi-calendar3"></i> ' + dateInfo.date + '</h6>';
                html += '      <small class="text-muted">' + dateInfo.day_name + '</small>';
                html += '    </div>';
                html += '    <button class="btn btn-sm btn-primary" onclick="openAddShopModal(\'' + dateInfo.date + '\')"><i class="bi bi-plus-circle"></i> Add Shop</button>';
                html += '  </div>';
                html += '  <div class="card-body">';

                if (dateInfo.shops && dateInfo.shops.length > 0) {
                    dateInfo.shops.forEach(function(shop, index) {
                        html += '<div class="shop-item d-flex justify-content-between align-items-center">';
                        html += '  <div>';
                        html += '    <strong>' + (index + 1) + '. ' + shop.shop_name + '</strong>';
                        if (shop.shop_type) {
                            html += ' <span class="badge bg-secondary">' + shop.shop_type + '</span>';
                        }
                        if (shop.arrival_time || shop.departure_time) {
                            html += '<br><small class="text-muted">';
                            if (shop.arrival_time) html += '<i class="bi bi-clock"></i> ' + shop.arrival_time;
                            if (shop.arrival_time && shop.departure_time) html += ' - ';
                            if (shop.departure_time) html += shop.departure_time;
                            html += '</small>';
                        }
                        if (shop.notes) {
                            html += '<br><small class="text-muted"><i class="bi bi-chat-left-text"></i> ' + shop.notes + '</small>';
                        }
                        html += '  </div>';
                        html += '  <div>';
                        html += '    <button class="btn btn-sm btn-outline-danger" onclick="deleteShop(' + shop.id + ')"><i class="bi bi-trash"></i></button>';
                        html += '  </div>';
                        html += '</div>';
                    });
                } else {
                    html += '<div class="text-muted text-center py-3"><i class="bi bi-shop"></i> No shops allocated for this date</div>';
                }

                html += '  </div>';
                html += '</div>';
            });

            container.html(html);
        }

        // Open add shop modal
        function openAddShopModal(date) {
            $('#allocation_date').val(date);
            $('#addShopForm')[0].reset();
            $('#shop_name').val('').trigger('change');
            $('#addShopModalLabel').text('Add Shop - ' + date);
            $('#addShopModal').modal('show');
        }

        // Save shop
        $('#saveShopBtn').on('click', function() {
            var tour_id = $('#tour_id').val();
            var allocation_date = $('#allocation_date').val();
            var shop_name = $('#shop_name').val();
            var shop_type = $('#shop_type').val();
            var arrival_time = $('#arrival_time').val();
            var departure_time = $('#departure_time').val();
            var shop_notes = $('#shop_notes').val();

            if (!shop_name) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Please select a shop',
                });
                return;
            }

            $.ajax({
                url: '<?php echo base_url('dashboard2/transport/add_shop_allocation') ?>',
                type: 'POST',
                data: {
                    tour_id: tour_id,
                    allocation_date: allocation_date,
                    shop_id: shop_name,
                    shop_type: shop_type,
                    arrival_time: arrival_time,
                    departure_time: departure_time,
                    notes: shop_notes
                },
                dataType: 'json',
                success: function(data) {
                    if (data.status == 200) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: data.message || 'Shop added successfully',
                            timer: 1500,
                            showConfirmButton: false
                        });
                        $('#addShopModal').modal('hide');
                        loadDateWiseShops(tour_id);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message || 'Failed to add shop',
                        });
                    }
                },
                error: function(xhr, status, error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An error occurred while saving the shop',
                    });
                }
            });
        });

        // Delete shop
        function deleteShop(shop_id) {
            Swal.fire({
                title: 'Are you sure?',
                text: "You want to remove this shop allocation?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    var tour_id = $('#tour_id').val();
                    $.ajax({
                        url: '<?php echo base_url('dashboard2/transport/delete_shop_allocation') ?>',
                        type: 'POST',
                        data: {
                            shop_id: shop_id
                        },
                        dataType: 'json',
                        success: function(data) {
                            if (data.status == 200) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Deleted!',
                                    text: data.message || 'Shop allocation has been removed.',
                                    timer: 1500,
                                    showConfirmButton: false
                                });
                                loadDateWiseShops(tour_id);
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: data.message || 'Failed to delete shop allocation',
                                });
                            }
                        },
                        error: function(xhr, status, error) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'An error occurred while deleting the shop',
                            });
                        }
                    });
                }
            });
        }
    </script>

</body>

</html>
