<?= view('layouts/head') ?>

<body>

    <style>
        .t-card {
            background: linear-gradient(135deg, #405189 0%, rgb(23, 28, 47) 100%);
            border-radius: 10px;
            padding: 20px;
            cursor: pointer;
            height: 220px;
        }

        .t-card-2 {
            background: linear-gradient(135deg, #405189 0%, rgb(23, 28, 47) 100%);
            border-radius: 10px;
            padding: 20px;
            height: 220px;
        }

        .t-card:hover {
            transition: background-color 0.3s ease-in-out;
            background: linear-gradient(315deg, rgb(23, 28, 47) 0%, rgb(87, 99, 143) 100%);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }

        .tranparent-bg-white {
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            width: fit-content;
        }

        .rct_td{
            cursor: pointer;
        }
    </style>

    <div class="d-flex justify-content-between align-items-center bg-white p-2 px-4 shadow fixed-top">
        <div>
            <img src="<?= base_url('assets/images/Logo-new.png') ?>" alt="" width="120px">
        </div>
        <div>
            <button class="btn btn-outline-dark" onclick="window.location.href='<?php echo base_url(); ?>dashboard'">
                <i class="bi bi-house-door-fill"></i> Home
            </button>
        </div>
    </div>
    <div class="container-fluid p-4 mt-5">
        <div class="row">
            <div class="col-md-2">
                <div class="card t-card" id="tour_search">
                    <div class="card-body">
                        <div class="d-flex flex-column justify-content-center align-items-center">
                            <div class="text-center p-3 px-4 tranparent-bg-white">
                                <i class="bi bi-search text-white fs-1"></i>
                            </div>
                            <div class="text-center mt-3">
                                <h3 class="fw-bold text-white">Tour Search</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card t-card" id="new_booking">
                    <div class="card-body">
                        <div class="d-flex flex-column justify-content-center align-items-center">
                            <div class="text-center p-3 px-4 tranparent-bg-white">
                                <i class="bi bi-plus-lg text-white fs-1"></i>
                            </div>
                            <div class="text-center mt-3">
                                <h3 class="fw-bold text-white">New Booking</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card t-card" id="transport">
                    <div class="card-body">
                        <div class="d-flex flex-column justify-content-center align-items-center">
                            <div class="text-center p-3 px-4 tranparent-bg-white">
                                <i class="bi bi-car-front text-white fs-1"></i>
                            </div>
                            <div class="text-center mt-3">
                                <h3 class="fw-bold text-white">Transport</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card t-card">
                    <div class="card-body">
                        <div class="d-flex flex-column justify-content-center align-items-center">
                            <div class="text-center p-3 px-4 tranparent-bg-white">
                                <i class="bi bi-unlock text-white fs-1"></i>
                            </div>
                            <div class="text-center mt-3">
                                <h3 class="fw-bold text-white">Driver Login</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card t-card">
                    <div class="card-body">
                        <div class="d-flex flex-column justify-content-center align-items-center">
                            <div class="text-center p-3 px-4 tranparent-bg-white">
                                <i class="bi bi-person-walking text-white fs-1"></i>
                            </div>
                            <div class="text-center mt-3">
                                <h3 class="fw-bold text-white">Arrivals</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card t-card">
                    <div class="card-body">
                        <div class="d-flex flex-column justify-content-center align-items-center">
                            <div class="text-center p-3 px-4 tranparent-bg-white">
                                <i class="bi bi-check-circle text-white fs-1"></i>
                            </div>
                            <div class="text-center mt-3">
                                <h3 class="fw-bold text-white">Confirmation</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card t-card">
                    <div class="card-body">
                        <div class="d-flex flex-column justify-content-center align-items-center">
                            <div class="text-center p-3 px-4 tranparent-bg-white">
                                <i class="bi bi-arrow-bar-down text-white fs-1"></i>
                            </div>
                            <div class="text-center mt-3">
                                <h3 class="fw-bold text-white">Payment Receivable</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card t-card">
                    <div class="card-body">
                        <div class="d-flex flex-column justify-content-center align-items-center">
                            <div class="text-center p-3 px-4 tranparent-bg-white">
                                <i class="bi bi-arrow-bar-up text-white fs-1"></i>
                            </div>
                            <div class="text-center mt-3">
                                <h3 class="fw-bold text-white">Payment Payable</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <div class="card t-card-2 p-1" style="cursor: default;">
                    <div class="card-body">
                        <div class="d-flex flex-column justify-content-start">
                            <div class="p-1 px-3 tranparent-bg-white text-white fw-boldd">
                                <i class="bi bi-arrow-counterclockwise me-2 text-white"></i> Recent Bookings
                            </div>
                            <div class="d-flex flex-column mt-2" id="recent_bookings">
                                <style>
                                    #recent_bookings .table th,
                                    #recent_bookings .table td {
                                        vertical-align: middle;
                                        border-color: rgb(128, 140, 183);
                                    }

                                    #recent_bookings .table th {
                                        color: #f8f9fa;
                                    }

                                    #recent_bookings .table td {
                                        color: #f8f9fa;
                                    }

                                    #recent_bookings .table-hover tbody tr:hover {
                                        background-color: rgba(7, 5, 30, 0.2);
                                    }
                                </style>
                                <table class="table table-sm table-hover table-striped align-middle">
                                    <!-- <thead>
                                        <tr>
                                            <th style="width: 5%">#</th>
                                            <th style="width: 20%">ID</th>
                                            <th>Name</th>
                                            <th style="width: 20%"></th>
                                            <th style="width: 15%"></th>
                                        </tr>
                                    </thead> -->
                                    <tbody>
                                        <?php if (empty($recent_tours)) { ?>
                                            <tr>
                                                <td colspan="5" class="text-center text-muted">
                                                    <i class="bi bi-search me-2"></i> No recent bookings found.
                                                </td>
                                            </tr>
                                        <?php } else { ?>
                                            <?php foreach ($recent_tours as $index => $tour) { ?>
                                                <tr>
                                                    <td class="rct_td" data-id="<?= $tour['tour_id']; ?>" style="width: 4%"><?php echo $index + 1; ?>.</td>
                                                    <td class="rct_td" data-id="<?= $tour['tour_id']; ?>" style="width: 15%">
                                                        <div class="d-flex flex-column">
                                                            <strong class="text-danger"><?php echo $tour['manual_id']; ?></strong>
                                                            <small>ID: <strong><?php echo $tour['tour_id']; ?></strong></small>
                                                        </div>
                                                    </td>
                                                    <td class="rct_td" data-id="<?= $tour['tour_id']; ?>">
                                                        <div class="d-flex flex-column">
                                                            <small style="color:rgb(119, 223, 255);"><strong><?php echo $tour['consignee']; ?></strong></small>
                                                            <small style="color:rgb(188, 188, 188);"><strong><?php echo $tour['agent_name']; ?></strong></small>
                                                        </div>
                                                    </td>
                                                    <td class="rct_td" data-id="<?= $tour['tour_id']; ?>" style="width: 15%">
                                                        <div class="d-flex flex-column">
                                                            <?php if ($tour['quotation'][0]['confirm'] == 1) { ?>
                                                                <small class="badge text-bg-success text-white mb-1" style="width: fit-content;">Confirmed</small>
                                                            <?php } else { ?>
                                                                <small class="badge text-bg-warning text-white mb-1" style="width: fit-content;">Not Confirmed</small>
                                                            <?php } ?>
                                                            <?php if ($tour['quotation'][0]['finalize'] == 1) { ?>
                                                                <small class="badge text-bg-success text-white" style="width: fit-content;">Finalized</small>
                                                            <?php } else { ?>
                                                                <small class="badge text-bg-warning text-white" style="width: fit-content;">Not Finalized</small>
                                                            <?php } ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php } ?>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="tourSearchModal" tabindex="-1" aria-labelledby="tourSearchModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="tourSearchModalLabel">
                        <i class="bi bi-search me-2"></i> Search Tours
                    </h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <input type="hidden" id="modal_value">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-10">
                            <input type="text" class="form-control" id="tour_search_input" placeholder="Search by tour ID, manual ID, or consignee name...">
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-primary w-100" id="tour_search_btn">
                                <i class="bi bi-search me-1"></i> Search
                            </button>
                        </div>
                    </div>
                    <div class="rounded p-3 mt-3" style="border: 1px solid rgb(210, 218, 218);">
                        <table class="table table-sm table-hover table-striped align-middle">
                            <thead>
                                <tr>
                                    <th style="width: 5%">#</th>
                                    <th style="width: 20%">ID</th>
                                    <th>Name</th>
                                    <th>Dates</th>
                                    <th style="width: 20%"></th>
                                    <th style="width: 15%"></th>
                                </tr>
                            </thead>
                            <tbody id="tour_search_results">
                                <tr>
                                    <!-- <td>1. </td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span>ID: <strong>1234</strong></span>
                                            <span>Manual ID: <strong>LR-256</strong></span>
                                        </div>
                                    </td>
                                    <td>John Doe</td>
                                    <td>
                                        <button class="btn btn-success btn-sm">View Details</button>
                                    </td> -->
                                    <td colspan="5">
                                        <div class="text-center text-muted">
                                            <i class="bi bi-search me-2"></i> Enter a keyword to search for tours.
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal" id="loadingModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="loadingModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="width: 300px; height: 80px; margin-left: 100px; margin-bottom: 100px;">
                <div class="modal-body">
                    <div class="d-flex flex-column align-items-center justify-content-center">
                        <img src="<?php echo base_url() ?>assets/images/loading.gif" width="30px">
                        <label class="form-label">Just a moment...</label>
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
        // #tour_search click event
        $('#tour_search').on('click', function() {
            $('#tourSearchModal').modal('show');
            $('#modal_value').val(1);
        });

        $('#transport').on('click', function() {
            $('#tourSearchModal').modal('show');
            $('#modal_value').val(2);
        });

        // #tour_search_input keyup event
        $('#tour_search_input').on('keyup', function(event) {
            if (event.key === 'Enter') {
                $('#tour_search_btn').trigger('click');
            }
        });

        // #tour_search_btn click event
        $('#tour_search_btn').on('click', function() {
            const keyword = $('#tour_search_input').val()?.trim();
            var modalValue = $('#modal_value').val();

            $.ajax({
                url: '<?php echo base_url(); ?>/dashboard2/searchTours',
                type: 'GET',
                data: {
                    keyword: keyword
                },
                dataType: 'json',
                beforeSend: function() {
                    $('#loadingModal').modal('show');
                },
                success: function(response) {
                    console.log(response);
                    const tbody = $('#tour_search_results');
                    tbody.empty();
                    if (response.status === 200) {
                        response.data?.forEach((tour, index) => {
                            tbody.append(`
                                <tr>
                                    <td>${index + 1}.</td>
                                    <td> 
                                        <div class="d-flex flex-column">
                                            <strong class="text-danger">${tour.manual_id}</strong>
                                            <span>ID: <strong>${tour.tour_id}</strong></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span class="text-secondary"><strong>${tour.consignee}</strong></span>
                                            <span class="text-muted">Agent: <strong>${tour.agent_name}</strong></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span class="text-secondary"> <strong>${tour.start_date}</strong></span>
                                            <span class="text-secondary"> <strong>${tour.end_date}</strong></span>
                                        </div>
                                    <td>
                                        <div class="d-flex flex-column">
                                            ${tour.quotation[0].confirm == 1 ? '<small class="badge text-bg-success mb-1">Confirmed</small>' : '<small class="badge text-bg-warning mb-1">Not Confirmed</small>'}
                                            ${tour.quotation[0].finalize == 1 ? '<small class="badge text-bg-success">Finalized</small>' : '<small class="badge text-bg-warning">Not Finalized</small>'}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-center">
                                            ${
                                                modalValue == 1
                                                ? `<a href="<?= base_url('dashboard2/tour_quotation?tour_id=') ?>${tour.tour_id}" class="btn btn-light border btn-sm">View Details</a>
                                                <a href="<?= base_url('dashboard2/reservation_card?tour_id=') ?>${tour.tour_id}" class="btn btn-primary border btn-sm mx-2">Reservation Card</a>
                                                <a href="<?= base_url('dashboard2/itenerary_print?tour_id=') ?>${tour.tour_id}" class="btn btn-warning border btn-sm">Itenerary Print</a>`
                                                : modalValue == 2 
                                                ? `<a href="<?= base_url('dashboard2/tour_transport?tour_id=') ?>${tour.tour_id}" class="btn btn-light border btn-sm" target="_blank">Driver Allocation</a>`
                                                : ''
                                            }
                                        </div>
                                    </td>
                                </tr>
                            `);
                        });
                    } else {
                        toastr.error(response.message || 'No tours found for the given keyword.');
                        tbody.append(`
                                <tr>
                                    <td colspan="4" class="text-center text-muted">
                                        <i class="bi bi-search me-2"></i> No tours found for the given keyword.
                                    </td>
                                </tr>
                            `);
                    }
                },
                error: function() {
                    toastr.error('An error occurred while searching for tours. Please try again later.');
                },
                complete: function() {
                    $('#loadingModal').modal('hide');
                }
            });
        });

        // #new_booking click event
        $('#new_booking').on('click', function() {
            window.location.href = '<?php echo base_url(); ?>dashboard2/new_quotation';
        });

        // .rct_td click event
        $(document).on('click', '.rct_td', function() {
            const tourId = $(this).data('id');
            window.location.href = '<?php echo base_url(); ?>dashboard2/tour_quotation?tour_id=' + tourId;
        });
    </script>
</body>


</html>