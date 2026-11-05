<?= view('layouts/head') ?>

<body>

    <?= view('layouts/topbar', ['topbar_back' => 'close', 'topbar_home' => false, 'topbar_logout' => false]) ?>
    <div class="container-fluid p-4 mt-5">

        <div class="row clearfix mt-3">

            <div class="col-md-6">
                <div class="card p-4">
                    <div class="body">

                        <div class="row clearfix mt-3">
                            <div class="col-md-12">
                                <div class="col-md-12">
                                    <div class="card p-3">
                                        <div class="body">
                                            <div class="row">
                                                <?php
                                                $consignee = [];
                                                foreach ($recent_tours as $detail) {
                                                    $consignee = $detail;
                                                ?>

                                                    <div class="col-md-12">
                                                        <div class="d-flex flex-column">
                                                            <span class="text-muted">Tour Name</span>
                                                            <h6 class="fw-bold"
                                                                id="t_consignee"><?php echo $detail['consignee'] ?></h6>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="d-flex flex-column">
                                                            <span class="text-muted">Agent</span>
                                                            <h6 class="fw-bold"
                                                                id="t_agent_name"><?php echo $detail['agent_name'] ?></h6>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="d-flex flex-column">
                                                            <span class="text-muted">Passengers</span>
                                                            <h6 class="fw-bold"
                                                                id="t_passenger_count"><?php echo $detail['passenger_count'] ?></h6>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="d-flex flex-column">
                                                            <span class="text-muted">Start Date</span>
                                                            <h6 class="fw-bold"
                                                                id="t_start_date"><?php echo $detail['start_date'] ?></h6>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="d-flex flex-column">
                                                            <span class="text-muted">End Date</span>
                                                            <h6 class="fw-bold"
                                                                id="t_end_date"><?php echo $detail['end_date'] ?></h6>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-12">
                                                        <div class="d-flex flex-column">
                                                            <span class="text-muted">Manual ID</span>
                                                            <h6 class="fw-bold"
                                                                id="t_manual_id"><?php echo $detail['manual_id'] ?></h6>
                                                        </div>
                                                    </div>
                                                    <input type="hidden" name="finalize_txt" id="finalize_txt"
                                                        value="<?php echo $detail['finalize_txt'] ?>">
                                                <?php } ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row clearfix mt-3">
                            <div class="col-md-12">
                                <div class="card p-3">
                                    <div class="body">
                                        <div class="row">
                                            <div class="col-md-9">
                                                <div class="form-group">
                                                    <label for="driver_select">Select Driver</label>
                                                    <select class="form-control select2-driver" id="driver_select" name="driver_select" onchange="onDriverChange(this)">
                                                        <option value="">Select Driver</option>
                                                        <?php foreach ($drivers as $driver) : ?>
                                                            <option value="<?php echo $driver['Sup_Acc_No']; ?>" data-contactno="<?php echo $driver['Mobile']; ?>"><?php echo $driver['Name']; ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group mt-4">
                                                    <button type="button" class="btn btn-success" id="add_driver_btn" disabled>Add Driver</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="card p-3">
                                <div class="body">
                                    <h5 class="fw-bold">Driver List</h5>
                                    <table class="table table-bordered table-striped table-custom">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Manual ID</th>
                                                <th>Driver Name</th>
                                                <th>Driver ID</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody id="driver_allocation_table">
                                            <?php

                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12" id="pending_requests_wrapper" style="display:none;">
                            <div class="card p-3">
                                <div class="body">
                                    <h5 class="fw-bold">Pending Allocation Requests</h5>
                                    <table class="table table-bordered table-striped table-custom">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Driver Name</th>
                                                <th>Driver ID</th>
                                                <th>Requested By</th>
                                                <th>Requested Date</th>
                                            </tr>
                                        </thead>
                                        <tbody id="pending_requests_table">
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                    </div>

                </div>
            </div>
            <div class="col-md-6">
                <div class="card p-4">
                    <div class="body">

                        <div class="row clearfix mt-3">
                            <div class="col-md-12">
                                <div class="col-md-12">
                                    <div class="card p-3">
                                        <div class="body">
                                            <div class="row">
                                                <?php
                                                $startDate = isset($consignee['start_date']) ? $consignee['start_date'] : null;
                                                $endDate = isset($consignee['end_date']) ? $consignee['end_date'] : null;

                                                $startMonth = $startDate ? date('F Y', strtotime($startDate)) : '-';
                                                $endMonth = $endDate ? date('F Y', strtotime($endDate)) : '-';
                                                ?>
                                                <div class="col-12">
                                                    <label class="fw-bold">Tour Calendar</label>
                                                    <div id="tour-calendar"></div>
                                                </div>
                                            </div>
                                            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css">
                                            <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
                                            <script>
                                                let calendar;
                                                document.addEventListener('DOMContentLoaded', function() {
                                                    var calendarEl = document.getElementById('tour-calendar');
                                                    var startDate = "<?= $startDate ?>";
                                                    var endDate = "<?= $endDate ?>";
                                                    var events = [];
                                                    if (startDate && endDate) {
                                                        events.push({
                                                            title: 'Current Tour',
                                                            start: startDate,
                                                            end: (new Date(new Date(endDate).getTime() + 24 * 60 * 60 * 1000)).toISOString().slice(0, 10), // FullCalendar exclusive end
                                                            allDay: true,
                                                            color: '#007bff'
                                                        });
                                                    }
                                                    calendar = new FullCalendar.Calendar(calendarEl, {
                                                        initialView: 'dayGridMonth',
                                                        height: 350,
                                                        events: events,
                                                        initialDate: startDate || new Date(),
                                                        headerToolbar: {
                                                            left: 'prev,next today',
                                                            center: 'title',
                                                            right: ''
                                                        }
                                                    });
                                                    calendar.render();
                                                });

                                                function loadDriverTours(driverId) {
                                                    if (!driverId) return;

                                                    var startDate = "<?= $startDate ?>";
                                                    var startMonth = startDate ? String(new Date(startDate).getMonth() + 1).padStart(2, '0') : null;
                                                    var startYear = startDate ? new Date(startDate).getFullYear() : null;

                                                    var endDate = "<?= $endDate ?>";
                                                    var endMonth = endDate ? String(new Date(endDate).getMonth() + 1).padStart(2, '0') : null;
                                                    var endYear = endDate ? new Date(endDate).getFullYear() : null;

                                                    $.ajax({
                                                        url: '<?= base_url('dashboard2/transport/driver_allow/get_driver_tours') ?>',
                                                        type: 'GET',
                                                        data: {
                                                            driver_id: driverId,
                                                            start_month: startMonth,
                                                            start_year: startYear,
                                                            end_month: endMonth,
                                                            end_year: endYear
                                                        },
                                                        success: function(response) {
                                                            try {
                                                                const data = JSON.parse(response);

                                                                if (data.status === 200) {
                                                                    calendar.getEvents().forEach(event => {
                                                                        if (event.title !== 'Current Tour') {
                                                                            event.remove();
                                                                        }
                                                                    });

                                                                    if (Array.isArray(data.tours) && data.tours.length > 0) {
                                                                        data.tours.forEach(tour => {
                                                                            if (tour.tour_id !== "<?= $consignee['tour_id'] ?>") {
                                                                                calendar.addEvent({
                                                                                    title: (tour.manual_id ? ' ' + tour.manual_id + '' : ''),
                                                                                    start: tour.start_date,
                                                                                    end: (new Date(new Date(tour.end_date).getTime() + 24 * 60 * 60 * 1000)).toISOString().slice(0, 10),
                                                                                    allDay: true,
                                                                                    color: '#78b848',
                                                                                    extendedProps: {
                                                                                        manual_id: tour.manual_id,
                                                                                        vehicle_name: tour.vehicle_name
                                                                                    }
                                                                                });
                                                                            }
                                                                        });
                                                                    }
                                                                } else {
                                                                    console.error("Error loading driver tours:", data.message);
                                                                }
                                                            } catch (e) {
                                                                console.error("Error parsing driver tours response:", e);
                                                            }
                                                        },
                                                        error: function(xhr, status, error) {
                                                            console.error("AJAX error when loading driver tours:", error);
                                                        }
                                                    });
                                                }

                                               function loadDriverPaymentStatus(driverId){
                                                    if (!driverId) return;

                                                    $.ajax({
                                                        url: '<?= base_url('dashboard2/transport/driver_allow/get_driver_payment_status') ?>',
                                                        type: 'GET',
                                                        data: {
                                                            driver_id: driverId
                                                        },
                                                        success: function(response) {
                                                            try {
                                                                const data = JSON.parse(response);

                                                                if (data.status === 200) {
                                                                    if(data.pending_payments_count !== 0){
                                                                        $('#add_driver_btn').prop('disabled', true);
                                                                        const tourList = data.tours.map(t => `<li>${t.manual_id}</li>`).join('');
                                                                        Swal.fire({
                                                                            icon: 'warning',
                                                                            title: 'Pending Payments',
                                                                            html: `This driver has <strong>${data.pending_payments_count}</strong> pending payment(s).<br><br>Affected Tours:<ul style="text-align:left;margin-top:8px;">${tourList}</ul>Please ensure all payments are settled before assigning new tours.`,
                                                                            confirmButtonText: 'OK',
                                                                            showDenyButton: true,
                                                                            denyButtonText: 'Request Allocation',
                                                                            denyButtonColor: '#f0ad4e'
                                                                        }).then((result) => {
                                                                            if (result.isDenied) {
                                                                                requestDriverAllocation(driverId);
                                                                            } else {
                                                                                location.reload();
                                                                            }
                                                                        });
                                                                    } else {
                                                                        $('#add_driver_btn').prop('disabled', false);
                                                                    }
                                                                } else if (data.status === 404) {
                                                                    // No tours found for this driver = no pending payments, allow allocation
                                                                    $('#add_driver_btn').prop('disabled', false);
                                                                } else {
                                                                    console.error("Error loading driver payment status:", data.message);
                                                                }
                                                            } catch (e) {
                                                                console.error("Error parsing driver payment status response:", e);
                                                            }
                                                        },
                                                        error: function(xhr, status, error) {
                                                            console.error("AJAX error when loading driver payment status:", error);
                                                        }
                                                    });
                                               }

                                               function requestDriverAllocation(driverId) {
                                                    const tourId = "<?= $consignee['tour_id'] ?>";
                                                    const manualId = "<?= $consignee['manual_id'] ?>";
                                                    const driverName = $('#driver_select option[value="' + driverId + '"]').text();

                                                    $.ajax({
                                                        url: '<?= base_url('dashboard2/transport/driver_allow/request_driver_allocation') ?>',
                                                        type: 'POST',
                                                        data: {
                                                            tour_id: tourId,
                                                            manual_id: manualId,
                                                            driver: driverId,
                                                            driver_name: driverName
                                                        },
                                                        success: function(response) {
                                                            const data = JSON.parse(response);
                                                            if (data.status === 200) {
                                                                Swal.fire({
                                                                    icon: 'success',
                                                                    title: 'Requested',
                                                                    text: data.message,
                                                                    confirmButtonText: 'OK'
                                                                }).then(() => {
                                                                    $('#driver_select').val('').trigger('change');
                                                                    updateDriverTable(tourId);
                                                                });
                                                            } else {
                                                                Swal.fire({
                                                                    icon: 'error',
                                                                    title: 'Error',
                                                                    text: data.message
                                                                });
                                                            }
                                                        },
                                                        error: function() {
                                                            Swal.fire({
                                                                icon: 'error',
                                                                title: 'Error',
                                                                text: 'Error occurred while requesting driver allocation.'
                                                            });
                                                        }
                                                    });
                                               }
                                            </script>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card p-4">
                    <div class="body">

                        <div class="row clearfix mt-3">
                            <div class="col-md-12">
                                <div class="col-md-12">
                                    <div class="card p-3">
                                        <div class="body">
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                                        <h5 class="fw-bold mb-0">Driver Availability Checker</h5>
                                                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#availableToursModal">
                                                            <i class="fa fa-calendar"></i> Tours Pending Driver Allocation
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="col-md-12 mb-3">
                                                    <input type="text" class="form-control" id="driver_availability_search" placeholder="Search by driver name, language, or vehicle type...">
                                                </div>
                                                <div class="col-md-12">
                                                    <div class="table-responsive">
                                                        <table class="table table-bordered table-striped table-custom">
                                                            <thead>
                                                                <tr>
                                                                    <th>#</th>
                                                                    <th>Driver Name</th>
                                                                    <th>Vehicle Type</th>
                                                                    <th style="width: 110px;">Phone Number</th>
                                                                    <th class="text-center">Unpaid Settlements</th>
                                                                    <th class="text-center">Tours This Month</th>
                                                                    <th class="text-center">Status</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody id="driver_availability_table">

                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                                <div class="col-md-12">
                                                    <nav>
                                                        <ul class="pagination justify-content-center" id="driver_availability_pagination"></ul>
                                                    </nav>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
       
        </div>
    </div>

    <!-- Available Tours Modal -->
    <div class="modal fade" id="availableToursModal" tabindex="-1" aria-labelledby="availableToursModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="availableToursModalLabel">Tours Pending Driver Allocation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive" style="max-height: 500px;">
                        <table class="table table-bordered table-striped table-hover">
                            <thead class="sticky-top bg-light">
                                <tr>
                                    <th style="width: 5%;">#</th>
                                    <th style="width: 12%;">Manual ID</th>
                                    <th style="width: 25%;">Tour Name</th>
                                    <th style="width: 15%;">Agent</th>
                                    <th style="width: 12%;">Start Date</th>
                                    <th style="width: 8%;">Passengers</th>
                                    <th style="width: 13%;">Action</th>
                                </tr>
                            </thead>
                            <tbody id="available_tours_modal_table">
                                <!-- Populated via JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <?= view('layouts/theme_settings') ?>
    <?= view('layouts/scripts') ?>
    <script src="<?php echo base_url(); ?>assets/bundles/morrisscripts.bundle.js"></script>
    <script src="<?php echo base_url(); ?>assets/vendor/echart/echarts.min.js"></script>
    <script src="<?php echo base_url(); ?>assets/js/pages/charts/echart.js"></script>

    <style>
        /* Fix Select2 vertical alignment */
        .select2-container--default .select2-selection--single {
            height: 38px !important;
            border: 1px solid #ced4da !important;
            display: flex !important;
            align-items: center !important;
        }
        
         .select2-selection__rendered {
            line-height: 12px !important;
            padding-left: 20px !important;
            padding-right: 30px !important;
            padding-top: 0 !important;
            padding-bottom: 0 !important;
            margin-top: -15px !important;
            vertical-align: middle !important;
        }

        .select2-selection__clear {
            margin-top: 8px !important;
        }

        .select2-selection__placeholder
        {
           margin-top: 5px !important;
           padding-top: 0 !important;
           padding-left: 0px !important;
           line-height: 60px !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 38px !important;
            top: 10use !important;
            right: 8px !important;
            display: flex !important;
            align-items: center !important;
        }
        
        .select2-container--default .select2-selection--single .select2-selection__arrow b {
            margin-top: 0 !important;
        }
        
        .select2-dropdown {
            border: 1px solid #ced4da !important;
        }
    </style>

    <script>
        $(document).ready(function() {
            const tourId = "<?php echo $consignee['tour_id']; ?>";
            updateDriverTable(tourId);
            loadDriverAvailabilityChecker();

            // Load available tours when modal is opened
            $('#availableToursModal').on('show.bs.modal', function() {
                updateAvailableToursTable();
            });

            // Initialize Select2 with autocomplete for driver select
            $('.select2-driver').select2({
                placeholder: 'Select Driver',
                allowClear: true,
                width: '100%',
                theme: 'bootstrap4',
                language: {
                    noResults: function() {
                        return "No drivers found";
                    }
                },
                escapeMarkup: function(markup) {
                    return markup;
                }
            });

            // Handle Assign button click for available drivers
            $(document).on('click', '.assign-driver-btn', function() {
                const driverId = $(this).data('driver-id');
                const driverName = $(this).data('driver-name');
                const tourId = "<?php echo $consignee['tour_id']; ?>";
                const manualId = "<?php echo $consignee['manual_id']; ?>";

                // Confirm assignment
                Swal.fire({
                    title: 'Assign Driver?',
                    text: `Assign ${driverName} to this tour?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#78b848',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, assign!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Call the existing driver allocation endpoint
                        $.ajax({
                            url: '<?= base_url('dashboard2/transport/driver_allow/driver_allocation') ?>',
                            type: 'POST',
                            data: {
                                tour_id: tourId,
                                manual_id: manualId,
                                driver: driverId,
                                driver_name: driverName
                            },
                            success: function(response) {
                                try {
                                    const data = JSON.parse(response);
                                    if (data.status === 200) {
                                        Swal.fire({
                                            icon: 'success',
                                            title: 'Success!',
                                            text: 'Driver assigned successfully',
                                            timer: 2000,
                                            showConfirmButton: false
                                        });
                                        // Close modal and refresh driver table
                                        $('#availableToursModal').modal('hide');
                                        updateDriverTable(tourId);
                                        loadDriverAvailabilityChecker();
                                    } else {
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'Error',
                                            text: data.message
                                        });
                                    }
                                } catch (e) {
                                    console.error("Error parsing response:", e);
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error',
                                        text: 'An error occurred while assigning the driver'
                                    });
                                }
                            },
                            error: function(xhr, status, error) {
                                console.error("AJAX error:", error);
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: 'Failed to assign driver'
                                });
                            }
                        });
                    }
                });
            });
        });

        function onDriverChange(selectElement) {
            const driverId = selectElement.value;
            const tourId = "<?php echo $consignee['tour_id']; ?>";
            const manualId = "<?php echo $consignee['manual_id']; ?>";
            const driverName = selectElement.options[selectElement.selectedIndex].text;

            if (driverId) {
                loadDriverTours(driverId);
                loadDriverPaymentStatus(driverId);
            } else {
                $('#add_driver_btn').prop('disabled', true);
            }
        }




        $('#add_driver_btn').on('click', function() {

            const driverId = $('#driver_select').val();
            const tourId = "<?php echo $consignee['tour_id']; ?>";
            const manualId = "<?php echo $consignee['manual_id']; ?>";
            const driverContactNo = $('#driver_select option:selected').data('contactno');
            const driverName = $('#driver_select option:selected').text();

            //validate drivercontactno
            if (!driverContactNo || driverContactNo.trim() === '') {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Selected driver does not have a valid contact number.',
                    showConfirmButton: true
                });
                return;
            }

            $.ajax({
                url: '<?= base_url('dashboard2/transport/driver_allow/driver_allocation') ?>',
                type: 'POST',
                data: {
                    driver: driverId,
                    tour_id: tourId,
                    manual_id: manualId,
                    driver_name: driverName
                },
                success: function(response) {
                    const data = JSON.parse(response);
                    if (data.status === 200) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: data.message,
                            showConfirmButton: true
                        }).then((result) => {
                            if (result.isConfirmed) {
                                updateDriverTable(tourId);
                                loadDriverAvailabilityChecker();
                            }
                        });
                        $('#driver_select').val('');
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message,
                            showConfirmButton: true
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error occurred while allocating driver.',
                        showConfirmButton: true
                    });
                }
            });

        })

        function updateDriverTable(tourId) {
            const tableBody = $('#driver_allocation_table');
            const pendingTableBody = $('#pending_requests_table');
            tableBody.empty();
            pendingTableBody.empty();
            $.ajax({
                url: '<?= base_url('dashboard2/transport/driver_allow/driver_allocation_details') ?>',
                type: 'GET',
                data: {
                    tour_id: tourId
                },
                success: function(response) {
                    const data = JSON.parse(response);
                    if (data.status === 200) {
                        if (Array.isArray(data.pending_requests) && data.pending_requests.length > 0) {
                            $('#pending_requests_wrapper').show();
                            data.pending_requests.forEach((req, index) => {
                                pendingTableBody.append(`
                                    <tr>
                                        <td>${index + 1}</td>
                                        <td>${req.driver_name}</td>
                                        <td>${req.driver}</td>
                                        <td>${req.requested_user || '-'}</td>
                                        <td>${req.requested_date || '-'}</td>
                                    </tr>
                                `);
                            });
                        } else {
                            $('#pending_requests_wrapper').hide();
                        }
                        if (Array.isArray(data.data)) {
                            data.data.forEach((driver, index) => {
                                tableBody.append(`
                                    <tr>
                                        <td>${index + 1}</td>
                                        <td>${driver.manual_id}</td>
                                        <td>${driver.driver_name}</td>
                                        <td>${driver.driver}</td>
                                        <td>
                                            <button class="btn btn-danger btn-sm" onclick="removeDriver('${driver.driver}', '${tourId}')">Remove</button>
                                        </td>
                                    </tr>
                                `);
                            });
                        }
                        // if (Array.isArray(data.transport_agent)) {
                        //     data.transport_agent.forEach((agent, index) => {
                        //         tableBody.append(`
                        //             <tr>
                        //                 <td>${data.data.length + index + 1}</td>
                        //                 <td>${agent.manual_id}</td>
                        //                 <td>${agent.driver_name}</td>
                        //                 <td>${agent.driver}</td>
                        //                 <td>
                        //                     <button class="btn btn-danger btn-sm" onclick="removeDriver('${agent.driver}', '${tourId}')">Remove</button>
                        //                 </td>
                        //             </tr>
                        //         `);
                        //     });
                        // }
                    } else {
                        alert(data.message);
                    }
                },
                error: function() {
                    alert('Error occurred while fetching driver allocations.');
                }
            });
        }

        function updateAvailableToursTable() {
            const tableBody = $('#available_tours_modal_table');
            tableBody.empty();

            // Show loading state
            tableBody.append(`
                <tr>
                    <td colspan="7" class="text-center">
                        <div class="spinner-border spinner-border-sm" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div> Loading available tours...
                    </td>
                </tr>
            `);

            $.ajax({
                url: '<?= base_url('dashboard2/transport/driver_allow/available_tours') ?>',
                type: 'GET',
                success: function(response) {
                    try {
                        const data = JSON.parse(response);
                        tableBody.empty();

                        if (data.status === 200) {
                            if (Array.isArray(data.data) && data.data.length > 0) {
                                data.data.forEach((tour, index) => {
                                    const startDate = new Date(tour.start_date).toLocaleDateString();
                                    tableBody.append(`
                                        <tr>
                                            <td>${index + 1}</td>
                                            <td>${tour.manual_id}</td>
                                            <td>${tour.consignee}</td>
                                            <td>${tour.agent_name || 'N/A'}</td>
                                            <td>${startDate}</td>
                                            <td class="text-center">${tour.passenger_count || '0'}</td>
                                            <td>
                                                <button class="btn btn-primary btn-sm load-tour-btn"
                                                        data-tour-id="${tour.tour_id}">
                                                    <i class="fa fa-external-link"></i> Load Tour
                                                </button>
                                            </td>
                                        </tr>
                                    `);
                                });
                            } else {
                                tableBody.append(`
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">No available tours found</td>
                                    </tr>
                                `);
                            }
                        } else {
                            tableBody.append(`
                                <tr>
                                    <td colspan="7" class="text-center text-danger">${data.message}</td>
                                </tr>
                            `);
                        }
                    } catch (e) {
                        console.error("Error parsing available tours response:", e);
                        tableBody.empty();
                        tableBody.append(`
                            <tr>
                                <td colspan="7" class="text-center text-danger">Error loading available tours</td>
                            </tr>
                        `);
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX error when fetching available tours:", error);
                    tableBody.empty();
                    tableBody.append(`
                        <tr>
                            <td colspan="7" class="text-center text-danger">Error occurred while fetching available tours</td>
                        </tr>
                    `);
                }
            });
        }

        // Handle Load Tour button click
        $(document).on('click', '.load-tour-btn', function() {
            const tourId = $(this).data('tour-id');
            window.location.href = '<?= base_url('dashboard2/transport/driver_allow/tour_transport') ?>?tour_id=' + tourId;
        });

        function removeDriver(driverId, tourId) {
            // First check if driver has pending advance request
            $.ajax({
                url: '<?= base_url('dashboard2/transport/driver_allow/advance_request_history') ?>',
                type: 'GET',
                data: {
                    tour_id: tourId,
                    agent: driverId
                },
                success: function(response) {
                    const advanceData = JSON.parse(response);

                    // Check if there are any pending advance requests (flag = 0)
                    let hasPendingAdvance = false;
                    if (advanceData.status === 200 && advanceData.data && Array.isArray(advanceData.data)) {
                        hasPendingAdvance = advanceData.data.some(function(request) {
                            return request.flag === 0;
                        });
                    }

                    if (hasPendingAdvance) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Pending Advance Request',
                            text: 'There is a pending advance request for this driver. Please delete it before removing the driver.',
                            showConfirmButton: true,
                            confirmButtonText: 'Go to Advance Requests'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.href = '<?= base_url('dashboard2/transport/driver_allow/advance_request') ?>?tour_id=' + tourId;
                            }
                        });
                        return;
                    }

                    // If no pending advance, show confirmation dialog
                    Swal.fire({
                        title: 'Are you sure?',
                        text: "You won't be able to revert this!",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes, remove it!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $.ajax({
                                url: '<?= base_url('dashboard2/transport/driver_allow/remove_driver') ?>',
                                type: 'POST',
                                data: {
                                    driver_id: driverId,
                                    tour_id: tourId
                                },
                                success: function(response) {
                                    const data = JSON.parse(response);
                                    if (data.status === 200) {
                                        Swal.fire(
                                            'Removed!',
                                            data.message,
                                            'success'
                                        ).then(() => {
                                            updateDriverTable(tourId);
                                            loadDriverAvailabilityChecker();
                                        });
                                    } else {
                                        Swal.fire(
                                            'Error!',
                                            data.message,
                                            'error'
                                        );
                                    }
                                },
                                error: function() {
                                    Swal.fire(
                                        'Error!',
                                        'An error occurred while removing the driver.',
                                        'error'
                                    );
                                }
                            });
                        }
                    });
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error occurred while checking advance requests.',
                        showConfirmButton: true
                    });
                }
            });
        }

        let driverAvailabilityData = [];
        let driverAvailabilityPage = 1;
        const DRIVER_AVAILABILITY_PAGE_SIZE = 10;

        function loadDriverAvailabilityChecker() {
            const tourId = "<?php echo $consignee['tour_id']; ?>";
            const tableBody = $('#driver_availability_table');
            tableBody.empty();
            tableBody.append(`
                <tr>
                    <td colspan="7" class="text-center">
                        <div class="spinner-border spinner-border-sm" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div> Checking driver availability...
                    </td>
                </tr>
            `);

            $.ajax({
                url: '<?= base_url('dashboard2/transport/driver_allow/driver_availability_checker') ?>',
                type: 'GET',
                data: {
                    tour_id: tourId
                },
                success: function(response) {
                    try {
                        const data = JSON.parse(response);

                        if (data.status === 200 && Array.isArray(data.data)) {
                            driverAvailabilityData = data.data;
                        } else {
                            driverAvailabilityData = [];
                        }

                        driverAvailabilityPage = 1;
                        renderDriverAvailabilityTable();
                    } catch (e) {
                        console.error("Error parsing driver availability response:", e);
                        driverAvailabilityData = [];
                        tableBody.empty();
                        tableBody.append('<tr><td colspan="7" class="text-center text-danger">Error loading driver availability.</td></tr>');
                        $('#driver_availability_pagination').empty();
                    }
                },
                error: function() {
                    driverAvailabilityData = [];
                    tableBody.empty();
                    tableBody.append('<tr><td colspan="7" class="text-center text-danger">Error occurred while checking driver availability.</td></tr>');
                    $('#driver_availability_pagination').empty();
                }
            });
        }

        function getFilteredDriverAvailabilityData() {
            const term = $('#driver_availability_search').val().trim().toLowerCase();
            if (!term) {
                return driverAvailabilityData;
            }
            return driverAvailabilityData.filter(function(driver) {
                const name = (driver.driver_name || '').toLowerCase();
                const vehicleType = (driver.vehicle_type || '').toLowerCase();
                const language = (driver.language || '').toLowerCase();
                return name.includes(term) || vehicleType.includes(term) || language.includes(term);
            });
        }

        function renderDriverAvailabilityTable() {
            const tableBody = $('#driver_availability_table');
            const pagination = $('#driver_availability_pagination');
            tableBody.empty();
            pagination.empty();

            const filtered = getFilteredDriverAvailabilityData();

            if (filtered.length === 0) {
                tableBody.append('<tr><td colspan="7" class="text-center text-muted">No drivers found.</td></tr>');
                return;
            }

            const totalPages = Math.ceil(filtered.length / DRIVER_AVAILABILITY_PAGE_SIZE);
            if (driverAvailabilityPage > totalPages) {
                driverAvailabilityPage = totalPages;
            }
            if (driverAvailabilityPage < 1) {
                driverAvailabilityPage = 1;
            }

            const start = (driverAvailabilityPage - 1) * DRIVER_AVAILABILITY_PAGE_SIZE;
            const pageItems = filtered.slice(start, start + DRIVER_AVAILABILITY_PAGE_SIZE);

            pageItems.forEach((driver, index) => {
                const statusBadge = driver.is_available
                    ? '<span class="badge bg-success">Available</span>'
                    : '<span class="badge bg-danger">Busy</span>';
                const unpaidBadgeClass = driver.unpaid_settlements > 0 ? 'bg-danger' : 'bg-secondary';

                const languageSubtext = driver.language
                    ? `<br><small class="text-muted">${escapeHtml(driver.language)}</small>`
                    : '';

                tableBody.append(`
                    <tr>
                        <td>${start + index + 1}</td>
                        <td>${escapeHtml(driver.driver_name || '-')}${languageSubtext}</td>
                        <td>${escapeHtml(driver.vehicle_type || '-')}</td>
                        <td>${escapeHtml(driver.mobile || '-')}</td>
                        <td class="text-center"><span class="badge ${unpaidBadgeClass}">${driver.unpaid_settlements}</span></td>
                        <td class="text-center">${driver.tours_this_month}</td>
                        <td class="text-center">${statusBadge}</td>
                    </tr>
                `);
            });

            if (totalPages <= 1) {
                return;
            }

            pagination.append(`
                <li class="page-item${driverAvailabilityPage === 1 ? ' disabled' : ''}">
                    <a class="page-link" href="#" data-page="${driverAvailabilityPage - 1}">Previous</a>
                </li>
            `);
            for (let i = 1; i <= totalPages; i++) {
                pagination.append(`
                    <li class="page-item${i === driverAvailabilityPage ? ' active' : ''}">
                        <a class="page-link" href="#" data-page="${i}">${i}</a>
                    </li>
                `);
            }
            pagination.append(`
                <li class="page-item${driverAvailabilityPage === totalPages ? ' disabled' : ''}">
                    <a class="page-link" href="#" data-page="${driverAvailabilityPage + 1}">Next</a>
                </li>
            `);
        }

        $(document).on('click', '#driver_availability_pagination .page-link', function(e) {
            e.preventDefault();
            const page = parseInt($(this).data('page'));
            if (!page || page === driverAvailabilityPage) {
                return;
            }
            driverAvailabilityPage = page;
            renderDriverAvailabilityTable();
        });

        $(document).on('input', '#driver_availability_search', function() {
            driverAvailabilityPage = 1;
            renderDriverAvailabilityTable();
        });

        function escapeHtml(value) {
            return $('<div>').text(value == null ? '' : value).html();
        }
    </script>
</body>


</html>