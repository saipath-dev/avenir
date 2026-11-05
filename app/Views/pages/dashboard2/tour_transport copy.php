<?= view('layouts/head') ?>

<body>

    <div class="d-flex justify-content-between align-items-center bg-white p-2 px-4 shadow fixed-top" style="display: flex;">
        <div>
            <img src="<?= base_url('assets/images/Logo-new.png') ?>" alt="" width="120px">
        </div>
        <div>
            <button class="btn btn-outline-dark" onclick="window.close()">
                <i class="bi bi-arrow-left"></i> Back
            </button>
        </div>
    </div>
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
                                        <div class="form-group">
                                            <label for="driver_select">Select Driver</label>
                                            <select class="form-select" id="driver_select" name="driver_select" onchange="onDriverChange(this)">
                                                <option value="">Select Driver</option>
                                                <?php foreach ($drivers as $driver) : ?>
                                                    <option value="<?php echo $driver['Sup_Acc_No']; ?>"><?php echo $driver['Name']; ?></option>
                                                <?php endforeach; ?>
                                            </select>
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
                                                // Get tour start and end dates from $consignee
                                                $startDate = isset($consignee['start_date']) ? $consignee['start_date'] : null;
                                                $endDate = isset($consignee['end_date']) ? $consignee['end_date'] : null;

                                                // Format months (e.g., "June 2024")
                                                $startMonth = $startDate ? date('F Y', strtotime($startDate)) : '-';
                                                $endMonth = $endDate ? date('F Y', strtotime($endDate)) : '-';
                                                ?>
                                                <div class="col-12 mb-2">
                                                    <label class="fw-bold">Tour Start Month</label>
                                                    <div class="form-control bg-light"><?= $startMonth ?></div>
                                                </div>
                                                <div class="col-12 mb-2">
                                                    <label class="fw-bold">Tour End Month</label>
                                                    <div class="form-control bg-light"><?= $endMonth ?></div>
                                                </div>
                                                <div class="col-12">
                                                    <label class="fw-bold">Tour Calendar</label>
                                                    <div id="tour-calendar"></div>
                                                </div>
                                            </div>
                                            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css">
                                            <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
                                            <script>
                                                document.addEventListener('DOMContentLoaded', function() {
                                                    var calendarEl = document.getElementById('tour-calendar');
                                                    var startDate = "<?= $startDate ?>";
                                                    var endDate = "<?= $endDate ?>";
                                                    var events = [];
                                                    if (startDate && endDate) {
                                                        events.push({
                                                            title: 'Tour',
                                                            start: startDate,
                                                            end: (new Date(new Date(endDate).getTime() + 24*60*60*1000)).toISOString().slice(0,10), // FullCalendar exclusive end
                                                            allDay: true,
                                                            color: '#007bff'
                                                        });
                                                    }
                                                    var calendar = new FullCalendar.Calendar(calendarEl, {
                                                        initialView: 'dayGridMonth',
                                                        height: 350,
                                                        events: events,
                                                        validRange: startDate && endDate ? { start: startDate, end: (new Date(new Date(endDate).getTime() + 24*60*60*1000)).toISOString().slice(0,10) } : undefined,
                                                        headerToolbar: {
                                                            left: 'prev,next today',
                                                            center: 'title',
                                                            right: ''
                                                        }
                                                    });
                                                    calendar.render();
                                                });
                                            </script>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card p-4">
                    <div class="body">

                        <div class="row clearfix mt-3">
                            <div class="col-md-12">
                                <div class="col-md-12">
                                    <div class="card p-3">
                                        <div class="body">
                                            <div class="row">

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

    <?= view('layouts/theme_settings') ?>
    <?= view('layouts/scripts') ?>
    <script src="<?php echo base_url(); ?>assets/bundles/morrisscripts.bundle.js"></script>
    <script src="<?php echo base_url(); ?>assets/vendor/echart/echarts.min.js"></script>
    <script src="<?php echo base_url(); ?>assets/js/pages/charts/echart.js"></script>

    <script>
        $(document).ready(function() {
            // Load driver allocation details on page load
            const tourId = "<?php echo $consignee['tour_id']; ?>";
            updateDriverTable(tourId);
        });

        function onDriverChange(selectElement) {
            const driverId = selectElement.value;
            const tourId = "<?php echo $consignee['tour_id']; ?>";
            const manualId = "<?php echo $consignee['manual_id']; ?>";
            const driverName = selectElement.options[selectElement.selectedIndex].text;

            // if (driverId) {
            //     $.ajax({
            //         url: '<?//= base_url('dashboard2/driver_allocation') ?>',
            //         type: 'POST',
            //         data: {
            //             driver: driverId,
            //             tour_id: tourId,
            //             manual_id: manualId,
            //             driver_name: driverName
            //         },
            //         success: function(response) {
            //             const data = JSON.parse(response);
            //             if (data.status === 200) {
            //                 Swal.fire({
            //                     icon: 'success',
            //                     title: 'Success',
            //                     text: data.message,
            //                     showConfirmButton: true
            //                 }).then((result) => {
            //                     if (result.isConfirmed) {
            //                         updateDriverTable(tourId);
            //                     }
            //                 });
            //                 // Reset the driver select dropdown after successful allocation
            //                 $('#driver_select').val('');
            //             } else {
            //                 Swal.fire({
            //                     icon: 'error',
            //                     title: 'Error',
            //                     text: data.message,
            //                     showConfirmButton: true
            //                 });
            //             }
            //         },
            //         error: function() {
            //             alert('Error occurred while allocating driver.');
            //         }
            //     });
            // }


        }

        function updateDriverTable(tourId) {
            const tableBody = $('#driver_allocation_table');
            tableBody.empty();
            $.ajax({
                url: '<?= base_url('dashboard2/driver_allocation_details') ?>',
                type: 'GET',
                data: {
                    tour_id: tourId
                },
                success: function(response) {
                    const data = JSON.parse(response);
                    if (data.status === 200) {
                        console.log(data);
                        // Render driver allocations
                        if (Array.isArray(data.data)) {
                            data.data.forEach((driver, index) => {
                                tableBody.append(`
                                    <tr>
                                        <td>${index + 1}</td>
                                        <td>${driver.manual_id}</td>
                                        <td>${driver.driver_name}</td>
                                        <td>${driver.driver}</td>
                                    </tr>
                                `);
                            });
                        }
                        // Render transport agent allocations (if any)
                        if (Array.isArray(data.transport_agent)) {
                            data.transport_agent.forEach((agent, index) => {
                                tableBody.append(`
                                    <tr>
                                        <td>${data.data.length + index + 1}</td>
                                        <td>${agent.manual_id}</td>
                                        <td>${agent.driver_name}</td>
                                        <td>${agent.driver}</td>
                                    </tr>
                                `);
                            });
                        }
                    } else {
                        alert(data.message);
                    }
                },
                error: function() {
                    alert('Error occurred while fetching driver allocations.');
                }
            });
        }
    </script>
</body>


</html>