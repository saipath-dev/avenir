<?= view('layouts/head') ?>

<style>
    .autocomplete-results {
        position: absolute;
        z-index: 1000;
        background: white;
        border: 1px solid #ddd;
        border-radius: 4px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        max-height: 300px;
        overflow-y: auto;
        width: calc(100% - 2px);
        margin-top: 2px;
    }

    .autocomplete-item {
        padding: 10px 12px;
        cursor: pointer;
        border-bottom: 1px solid #f0f0f0;
    }

    .autocomplete-item:hover {
        background-color: #0069d9;
        color: white;
    }

    .autocomplete-item:last-child {
        border-bottom: none;
    }
</style>

<body>

    <!-- Begin page -->
    <div id="layout-wrapper">

        <?= view('layouts/header') ?>
        <?= view('layouts/sidebar') ?>

        <!-- ============================================================== -->
        <!-- Start right Content here -->
        <!-- ============================================================== -->
        <div class="main-content">

            <div class="page-content">
                <div class="container-fluid">

                    <div class="row">
                        <div class="col">

                            <div class="h-100">
                                <div class="row">
                                    <div class="col-md-10 d-flex">
                                        <h4 class="p-title">Usage Note</h4>
                                    </div>
                                </div>

                                <div class="row clearfix mt-3">
                                    <div class="col-md-4">
                                        <div class="card p-4">
                                            <div class="body">
                                                <div class="mb-3">
                                                    <label class="form-label" for="created_by_display">Created By</label>
                                                    <input class="form-control" type="text" id="created_by_display" name="created_by_display" value="<?= esc($created_by ?? 'Unknown') ?>" readonly />
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label" for="tour_search">Search Tour <span class="text-danger">*</span></label>
                                                    <input class="form-control" type="text" id="tour_search" name="tour_search" placeholder="Search by Manual ID or Tour Name" required />
                                                    <input type="hidden" id="selected_tour_id" name="selected_tour_id" value="">
                                                    <input type="hidden" id="selected_pax" name="selected_pax" value="">
                                                </div>
                                                <div class="mb-3" id="tour_details_section" style="display: none;">
                                                    <div class="alert alert-info">
                                                        <strong>Tour Name:</strong> <span id="tour_name_display">-</span><br>
                                                        <strong>Tour Dates:</strong> <span id="tour_dates_display">-</span><br>
                                                        <strong>Pax:</strong> <span id="tour_pax_display">-</span>
                                                    </div>
                                                </div>
                                                <form action="javascript:void(0);" id="form_submit" accept-charset="utf-8">
                                                    <fieldset><?= csrf_field() ?>
                                                        <div class="mb-3">
                                                            <label class="form-label" for="activity_select">Select Activity<font color="#FF0000"><strong>*</strong></font></label>
                                                            <select class="form-control" id="activity_select" name="activity_select" required disabled>
                                                                <option value="">Select a tour first</option>
                                                            </select>
                                                            <input type="hidden" id="activity_id" name="activity_id" value="">
                                                            <input type="hidden" id="activity_ref_no" name="activity_ref_no" value="">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label" for="item_name">Item<font color="#FF0000"><strong>*</strong></font></label>
                                                            <input class="form-control" type="text" id="item_name" name="item_name" placeholder="Enter Minimum a letter to search..." required disabled />
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label" for="item_code">Item Code<font color="#FF0000"><strong>*</strong></font></label>
                                                            <input class="form-control" type="text" id="item_code" name="item_code" placeholder="" required readonly />
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label" for="quantity">Quantity (Max: <span id="max_pax">0</span>)<font color="#FF0000"><strong>*</strong></font></label>
                                                            <input class="form-control form-control-lg" type="number" id="quantity" name="quantity" min="1" max="0" required disabled />
                                                            <small class="text-muted">Quantity cannot exceed Pax count</small>
                                                        </div>
                                                        <button type="submit" class="btn btn-success w-100px me-5px">Add</button>
                                                        <button type="reset" class="btn btn-default w-100px">Cancel</button>
                                                    </fieldset>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="card p-4">
                                            <div class="body">
                                                <div class="table-responsive">
                                                    <table class="table table-hover table-custom align-middle" id="itemTable">
                                                        <thead class="thead-dark">
                                                            <tr>
                                                                <th>#</th>
                                                                <th>Activity</th>
                                                                <th>Item Name</th>
                                                                <th>Item Code</th>
                                                                <th>Quantity</th>
                                                                <th>Actions</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="itemTableBody">
                                                            <tr>
                                                                <td colspan="6" class="text-center">No data found</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                                <button id="btn_finalize" type="button" class="btn btn-success mb-3 float-end"><i class="bi bi-check-circle me-1"></i> Finalize Usage Note</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div> <!-- end .h-100-->

                        </div> <!-- end col -->
                    </div>

                </div>
                <!-- container-fluid -->
            </div>
            <!-- End Page-content -->

            <?= view('layouts/brand') ?>

        </div>
        <!-- end main content-->
    </div>
    <!-- END layout-wrapper -->

    <?= view('layouts/inpage_footer') ?>
    <?= view('layouts/theme_settings') ?>
    <?= view('layouts/scripts') ?>

    <script>
        let localItems = [];
        let tourActivities = [];

        // Render table with items
        function renderTable(items) {
            const tbody = document.getElementById('itemTableBody');
            tbody.innerHTML = '';

            if (!items || items.length === 0) {
                const row = document.createElement('tr');
                row.innerHTML = '<td colspan="6" class="text-center">No data found</td>';
                tbody.appendChild(row);
            } else {
                items.forEach((item, index) => {
                    const row = document.createElement('tr');
                    row.setAttribute('data-index', index);
                    row.innerHTML = `
                <td>${index + 1}.</td>
                <td>${item.activity_name || 'N/A'}</td>
                <td>${item.item_name || 'N/A'}</td>
                <td>${item.item_code || 'N/A'}</td>
                <td>${item.quantity || '0'}</td>
                <td>
                    <button type="button" class="btn btn-sm btn-danger btnDelete"><i class="bi bi-trash"></i></button>
                </td>
            `;
                    tbody.appendChild(row);
                });
            }
        }

        // Custom autocomplete for tour search using existing searchTours endpoint
        let tourSearchTimeout;
        $("#tour_search").on('keyup', function() {
            const searchTerm = $(this).val();
            const $input = $(this);

            if (searchTerm.length < 2) {
                $input.next('.autocomplete-results').remove();
                return;
            }

            clearTimeout(tourSearchTimeout);
            tourSearchTimeout = setTimeout(function() {
                $.ajax({
                    url: "<?= base_url('inventory/usage_note/search_tours') ?>",
                    type: "GET",
                    data: { keyword: searchTerm },
                    success: function(response) {
                        console.log('Tour search response:', response);
                        $input.siblings('.autocomplete-results').remove();

                        // Parse response from existing endpoint (returns JSON string)
                        let data = [];

                        if (typeof response === 'string') {
                            try {
                                const parsed = JSON.parse(response);
                                console.log('Parsed response:', parsed);
                                if (parsed.status === 200 && parsed.data) {
                                    data = parsed.data;
                                } else if (Array.isArray(parsed)) {
                                    data = parsed;
                                }
                            } catch(e) {
                                console.error('Parse error:', e);
                            }
                        } else if (response.status === 200 && response.data) {
                            data = response.data;
                        } else if (Array.isArray(response)) {
                            data = response;
                        }

                        console.log('Final tour data:', data);

                        if (data && data.length > 0) {
                            const $results = $('<div class="autocomplete-results"></div>');

                            data.forEach(function(tour) {
                                const $item = $('<div class="autocomplete-item"></div>')
                                    .html(`<strong>${tour.consignee}</strong><br><small>${tour.manual_id || 'N/A'}</small>`)
                                    .data('tour', tour)
                                    .on('click', function() {
                                        const selectedTour = $(this).data('tour');
                                        selectTour(selectedTour);
                                        $results.remove();
                                    });
                                $results.append($item);
                            });

                            $input.after($results);
                        } else {
                            console.log('No tours found or empty data');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Tour search error:', error);
                        console.error('Response text:', xhr.responseText);
                    }
                });
            }, 300);
        });

        // Select tour and load activities
        function selectTour(tour) {
            $("#tour_search").val(tour.consignee + ' (' + (tour.manual_id || 'N/A') + ')');
            $("#selected_tour_id").val(tour.tour_id);
            $("#selected_pax").val(tour.passenger_count);

            // Display tour details
            $("#tour_name_display").text(tour.consignee);
            $("#tour_dates_display").text(tour.start_date + ' to ' + tour.end_date);
            $("#tour_pax_display").text(tour.passenger_count);
            $("#tour_details_section").show();

            // Update max quantity
            $("#max_pax").text(tour.passenger_count);
            $("#quantity").attr('max', tour.passenger_count);

            // Load activities for this tour
            loadTourActivities(tour.tour_id);
        }

        // Load tour activities - using optimized usage_note endpoint
        function loadTourActivities(tourId) {
            $.ajax({
                url: "<?= base_url('inventory/usage_note/get_tour_activities') ?>",
                type: "GET",
                data: { tour_id: tourId },
                success: function(response) {
                    console.log('Activities response:', response);

                    let data = [];
                    if (response.status === 200 && response.activities) {
                        data = response.activities;
                    } else if (Array.isArray(response)) {
                        data = response;
                    }

                    console.log('Activities data:', data);

                    if (data.length === 0) {
                        Swal.fire({
                            icon: 'info',
                            title: 'No Activities',
                            text: 'This tour has no entrance fee activities.'
                        });
                        return;
                    }

                    tourActivities = data;
                    const $select = $("#activity_select");
                    $select.html('<option value="">Select Activity</option>');

                    data.forEach(function(activity) {
                        // Format date for display
                        const date = activity.tour_date ? activity.tour_date.split('T')[0] : '';
                        const activityName = activity.name || 'N/A';
                        $select.append(`<option value="${activity.id}" data-ref="${activity.ref_no}">${activityName} (${activity.code}) - ${date}</option>`);
                    });

                    // Enable activity selection
                    $select.prop('disabled', false);
                },
                error: function(xhr, status, error) {
                    console.error('Activities error:', error);
                    console.error('Response:', xhr.responseText);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'Failed to load tour activities'
                    });
                }
            });
        }

        // Activity selection handler
        $("#activity_select").on('change', function() {
            const selectedValue = $(this).val();
            if (selectedValue) {
                const selectedOption = $(this).find('option:selected');
                $("#activity_id").val(selectedValue);
                $("#activity_ref_no").val(selectedOption.data('ref'));

                // Enable item search
                $("#item_name").prop('disabled', false);
                $("#quantity").prop('disabled', false);
            } else {
                $("#activity_id").val('');
                $("#activity_ref_no").val('');
                $("#item_name").prop('disabled', true);
                $("#quantity").prop('disabled', true);
            }
        });

        // Custom autocomplete for item search (no jQuery UI dependency)
        let itemSearchTimeout;
        $("#item_name").on('keyup', function() {
            const searchTerm = $(this).val();
            const $input = $(this);

            if (searchTerm.length < 1) {
                $input.siblings('.autocomplete-results').remove();
                return;
            }

            clearTimeout(itemSearchTimeout);
            itemSearchTimeout = setTimeout(function() {
                $.ajax({
                    url: "<?= base_url('inventory/usage_note/search_item') ?>",
                    type: "POST",
                    dataType: "json",
                    data: { term: searchTerm },
                    success: function(data) {
                        $input.siblings('.autocomplete-results').remove();

                        if (data.length > 0) {
                            const $results = $('<div class="autocomplete-results"></div>');

                            data.forEach(function(item) {
                                const $item = $('<div class="autocomplete-item"></div>')
                                    .text(item.label)
                                    .data('item', item)
                                    .on('click', function() {
                                        const selectedItem = $(this).data('item');
                                        $("#item_name").val(selectedItem.label);
                                        $("#item_code").val(selectedItem.stock_card);
                                        $("#quantity").focus();
                                        $results.remove();
                                    });
                                $results.append($item);
                            });

                            $input.after($results);
                        }
                    }
                });
            }, 300);
        });

        // Add item to temp table
        $("#form_submit").submit(function() {
            const tour_id = $("#selected_tour_id").val();
            const activity_id = $("#activity_id").val();
            const activity_ref_no = $("#activity_ref_no").val();
            const item_name = $("#item_name").val();
            const item_code = $("#item_code").val();
            const quantity = $("#quantity").val();
            const max_pax = parseInt($("#selected_pax").val());

            // Validation
            if (!tour_id) {
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: 'Please select a tour first!'
                });
                return false;
            }
            if (!activity_id) {
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: 'Please select an activity!'
                });
                return false;
            }
            if (!item_name || !item_code || !quantity) {
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: 'Please fill all the fields!'
                });
                return false;
            }
            if (quantity <= 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: 'Quantity should be greater than 0!'
                });
                return false;
            }
            if (parseInt(quantity) > max_pax) {
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: `Quantity cannot exceed Pax count (${max_pax})!`
                });
                return false;
            }

            // Get activity name
            const activity_name = $("#activity_select option:selected").text();

            // Prevent duplicate item_code for same activity
            if (localItems.some(item => item.item_code === item_code && item.activity_id === activity_id)) {
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: 'This item is already added for this activity!'
                });
                return false;
            }

            // Add to local array
            localItems.push({
                activity_id: activity_id,
                activity_ref_no: activity_ref_no,
                activity_name: activity_name,
                item_name: item_name,
                item_code: item_code,
                quantity: quantity
            });

            // Update table
            renderTable(localItems);

            // Clear fields
            $("#item_name").val('');
            $("#item_code").val('');
            $("#quantity").val('');
            $("#item_name").focus();

            return false;
        });

        // Delete item from temp table
        $(document).on('click', '.btnDelete', function() {
            const row = $(this).closest('tr');
            const index = $(row).attr('data-index');
            if (index === undefined) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Invalid item index'
                });
                return;
            }
            Swal.fire({
                title: 'Are you sure?',
                text: "Do you want to delete this item?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    localItems.splice(index, 1);
                    renderTable(localItems);
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Item deleted successfully'
                    });
                }
            });
        });

        // Finalize usage note
        $("#btn_finalize").click(function() {
            const tour_id = $("#selected_tour_id").val();

            if (!tour_id) {
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: 'Please select a tour first!'
                });
                return false;
            }
            if (localItems.length === 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: 'Please add items to finalize!'
                });
                return false;
            }
            Swal.fire({
                title: 'Are you sure?',
                text: "Do you want to finalize the Usage note?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, finalize it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "<?= base_url('inventory/usage_note/finalize_usage_note') ?>",
                        type: "POST",
                        dataType: "json",
                        contentType: "application/json",
                        data: JSON.stringify({
                            tour_id: tour_id,
                            items: localItems,
                            remarks: ''
                        }),
                        success: function(response) {
                            if (response.status === 'success') {
                                Swal.fire({
                                    title: 'Success!',
                                    text: 'Usage note created successfully and is pending authorization.',
                                    icon: 'success'
                                }).then(() => {
                                    if (response.print_url) {
                                        window.open(response.print_url, '_blank');
                                    }
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    title: 'Error!',
                                    text: response.message || 'Failed to finalize',
                                    icon: 'error'
                                });
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Finalize error:', error);
                            Swal.fire({
                                title: 'Error!',
                                text: 'Something went wrong!',
                                icon: 'error'
                            });
                        }
                    });
                }
            });
        });

        // Close autocomplete when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('#tour_search, #item_name, .autocomplete-results').length) {
                $('.autocomplete-results').remove();
            }
        });

        // Clear fields on backspace/delete
        $("#item_name, #item_code").on('input', function(event) {
            let key = event.originalEvent.inputType;
            if (key === 'deleteContentBackward' || key === 'deleteContentForward') {
                $("#item_code").val('');
                $("#item_name").val('');
            }
        });

        // Initialize on page load
        // Pre-populate Search Tour when arriving with ?tour_id=...&manual_id=... (e.g. from the
        // Transport Portal's "Create Usage Note" action button on a Required Entrance Ticket row).
        function prefillTourFromQueryString() {
            const params = new URLSearchParams(window.location.search);
            const tourId = params.get('tour_id');
            const manualId = params.get('manual_id');
            if (!tourId || !manualId) {
                return;
            }

            $.ajax({
                url: "<?= base_url('inventory/usage_note/search_tours') ?>",
                type: "GET",
                data: { keyword: manualId },
                success: function(response) {
                    let data = [];
                    if (typeof response === 'string') {
                        try {
                            const parsed = JSON.parse(response);
                            if (parsed.status === 200 && parsed.data) data = parsed.data;
                        } catch (e) { /* ignore */ }
                    } else if (response && response.status === 200 && response.data) {
                        data = response.data;
                    }

                    const match = data.find(t => String(t.tour_id) === String(tourId));
                    if (match) {
                        selectTour(match);
                    }
                }
            });
        }

        $(document).ready(function() {
            renderTable(localItems);
            prefillTourFromQueryString();
        });
    </script>

</body>

</html>
