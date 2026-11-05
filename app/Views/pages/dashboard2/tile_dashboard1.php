<?= view('layouts/head') ?>

<body>

    <style>
        :root {
            --mantis: #7ABC49;
            --white: #FFFFFF;
            --carolina-blue: #64ADD4;
            --hunyadi-yellow: #E7B34E;
            --mantis-2: #84C155;
            --text-dark: #2c3e50;
            --text-muted: #6c757d;
        }

        body {
            background-color: #f8f9fa;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in {
            animation: fadeInUp 0.6s ease-out;
        }

        .header-bar {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            transition: box-shadow 0.3s ease;
        }

        .t-card {
            background: var(--white);
            border-radius: 12px;
            padding: 20px;
            cursor: pointer;
            height: 220px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease-in-out;
            border: 2px solid transparent;
        }

        .t-card:hover {
            transform: translateY(-4px) scale(1.02);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        }

        .t-card-mantis {
            border-color: var(--mantis);
            background: rgba(122, 188, 73, 0.03);
        }

        .t-card-mantis:hover {
            background: rgba(122, 188, 73, 0.08);
        }

        .t-card-blue {
            border-color: var(--carolina-blue);
            background: rgba(100, 173, 212, 0.03);
        }

        .t-card-blue:hover {
            background: rgba(100, 173, 212, 0.08);
        }

        .t-card-yellow {
            border-color: var(--hunyadi-yellow);
            background: rgba(231, 179, 78, 0.03);
        }

        .t-card-yellow:hover {
            background: rgba(231, 179, 78, 0.08);
        }

        .t-card-mantis-2 {
            border-color: var(--mantis-2);
            background: rgba(132, 193, 85, 0.03);
        }

        .t-card-mantis-2:hover {
            background: rgba(132, 193, 85, 0.08);
        }

        .t-card-2 {
            background: var(--white);
            border-radius: 12px;
            padding: 20px;
            height: 220px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .icon-wrapper {
            background-color: var(--white);
            border-radius: 50%;
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .t-card:hover .icon-wrapper {
            transform: scale(1.1);
        }

        .icon-mantis {
            color: var(--mantis);
        }

        .icon-blue {
            color: var(--carolina-blue);
        }

        .icon-yellow {
            color: var(--hunyadi-yellow);
        }

        .icon-mantis-2 {
            color: var(--mantis-2);
        }

        .rct_td {
            cursor: pointer;
            transition: background-color 0.2s ease;
        }

        .section-header {
            background: var(--carolina-blue);
            color: var(--white);
            padding: 8px 16px;
            border-radius: 8px;
            width: fit-content;
            font-weight: 600;
        }

        .badge-success-custom {
            background-color: var(--mantis) !important;
        }

        .badge-warning-custom {
            background-color: var(--hunyadi-yellow) !important;
        }
    </style>

    <div class="d-flex justify-content-between align-items-center bg-white p-2 px-4 header-bar fixed-top">
        <div>
            <img src="<?= base_url('assets/images/Logo-new.png') ?>" alt="" width="120px">
        </div>
        <div>
            <button class="btn btn-search-custom" onclick="window.location.href='<?php echo base_url(); ?>dashboard'">
                <i class="bi bi-house-door-fill"></i> Home
            </button>
        </div>
    </div>
    <div class="container-fluid p-4 mt-5">
        <div class="row animate-fade-in">
            <div class="col-md-2">
                <div class="card t-card t-card-mantis" id="tour_search">
                    <div class="card-body">
                        <div class="d-flex flex-column justify-content-center align-items-center">
                            <div class="icon-wrapper">
                                <img src="https://img.icons8.com/office/80/search--v1.png" alt="search" width="50" />
                            </div>
                            <div class="text-center mt-3">
                                <h5 class="fw-bold" style="color: var(--text-dark);">Tour Search</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card t-card t-card-mantis" id="new_booking">
                    <div class="card-body">
                        <div class="d-flex flex-column justify-content-center align-items-center">
                            <div class="icon-wrapper">
                                <img src="https://img.icons8.com/office/80/add--v1.png" alt="new booking" width="50" />
                            </div>
                            <div class="text-center mt-3">
                                <h5 class="fw-bold" style="color: var(--text-dark);">New Booking</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card t-card t-card-blue" id="transport">
                    <div class="card-body">
                        <div class="d-flex flex-column justify-content-center align-items-center">
                            <div class="icon-wrapper">
                                <img src="https://img.icons8.com/office/80/bus--v1.png" alt="transport" width="50" />
                            </div>
                            <div class="text-center mt-3">
                                <h5 class="fw-bold" style="color: var(--text-dark);">Transport</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card t-card t-card-blue">
                    <div class="card-body">
                        <div class="d-flex flex-column justify-content-center align-items-center">
                            <div class="icon-wrapper">
                                <img src="https://img.icons8.com/office/80/steering-wheel.png" alt="driver login" width="50" />
                            </div>
                            <div class="text-center mt-3">
                                <h5 class="fw-bold" style="color: var(--text-dark);">Driver Login</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card t-card t-card-yellow">
                    <div class="card-body">
                        <div class="d-flex flex-column justify-content-center align-items-center">
                            <div class="icon-wrapper">
                                <img src="https://img.icons8.com/office/80/airplane-take-off.png" alt="arrivals" width="50" />
                            </div>
                            <div class="text-center mt-3">
                                <h5 class="fw-bold" style="color: var(--text-dark);">Arrivals</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card t-card t-card-yellow">
                    <div class="card-body">
                        <div class="d-flex flex-column justify-content-center align-items-center">
                            <div class="icon-wrapper">
                                <img src="https://img.icons8.com/office/80/checked--v1.png" alt="confirmation" width="50" />
                            </div>
                            <div class="text-center mt-3">
                                <h5 class="fw-bold" style="color: var(--text-dark);">Confirmation</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card t-card t-card-mantis-2">
                    <div class="card-body">
                        <div class="d-flex flex-column justify-content-center align-items-center">
                            <div class="icon-wrapper">
                                <img src="https://img.icons8.com/office/80/receive-cash.png" alt="payment receivable" width="50" />
                            </div>
                            <div class="text-center mt-3">
                                <h5 class="fw-bold" style="color: var(--text-dark);">Payment Receivable</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card t-card t-card-mantis-2">
                    <div class="card-body">
                        <div class="d-flex flex-column justify-content-center align-items-center">
                            <div class="icon-wrapper">
                                <img src="https://img.icons8.com/office/80/cash-in-hand.png" alt="payment payable" width="50" />
                            </div>
                            <div class="text-center mt-3">
                                <h5 class="fw-bold" style="color: var(--text-dark);">Payment Payable</h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <div class="card t-card-2 p-1" style="cursor: default;">
                    <div class="card-body">
                        <div class="d-flex flex-column justify-content-start">
                            <div class="section-header">
                                <i class="bi bi-arrow-counterclockwise me-2"></i> Recent Bookings
                            </div>
                            <div class="d-flex flex-column mt-2" id="recent_bookings">
                                <style>
                                    #recent_bookings .table th,
                                    #recent_bookings .table td {
                                        vertical-align: middle;
                                        border-color: rgba(100, 173, 212, 0.15);
                                    }

                                    #recent_bookings .table th {
                                        color: var(--text-dark);
                                        font-weight: 600;
                                        background-color: rgba(100, 173, 212, 0.08);
                                    }

                                    #recent_bookings .table td {
                                        color: var(--text-dark);
                                    }

                                    #recent_bookings .table-striped tbody tr:nth-of-type(odd) {
                                        background-color: rgba(100, 173, 212, 0.03);
                                    }

                                    #recent_bookings .table-hover tbody tr {
                                        transition: background-color 0.2s ease;
                                    }

                                    #recent_bookings .table-hover tbody tr:hover {
                                        background-color: rgba(100, 173, 212, 0.15) !important;
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
                                                            <small style="color: var(--carolina-blue);"><strong><?php echo $tour['consignee']; ?></strong></small>
                                                            <small style="color: var(--text-muted);"><strong><?php echo $tour['agent_name']; ?></strong></small>
                                                        </div>
                                                    </td>
                                                    <td class="rct_td" data-id="<?= $tour['tour_id']; ?>" style="width: 15%">
                                                        <div class="d-flex flex-column">
                                                            <?php if ($tour['quotation'][0]['confirm'] == 1) { ?>
                                                                <small class="badge badge-success-custom text-white mb-1" style="width: fit-content;">Confirmed</small>
                                                            <?php } else { ?>
                                                                <small class="badge badge-warning-custom text-white mb-1" style="width: fit-content;">Not Confirmed</small>
                                                            <?php } ?>
                                                            <?php if ($tour['quotation'][0]['finalize'] == 1) { ?>
                                                                <small class="badge badge-success-custom text-white" style="width: fit-content;">Finalized</small>
                                                            <?php } else { ?>
                                                                <small class="badge badge-warning-custom text-white" style="width: fit-content;">Not Finalized</small>
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

    <style>
        .modal-content {
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
            border: none;
        }

        .modal-header {
            background-color: var(--carolina-blue);
            color: var(--white);
            border-top-left-radius: 12px;
            border-top-right-radius: 12px;
            border-bottom: none;
        }

        .modal-header .btn-close {
            filter: brightness(0) invert(1);
        }

        .btn-search-custom {
            background: linear-gradient(135deg, var(--mantis) 0%, var(--mantis-2) 100%);
            color: var(--white);
            border: none;
            font-weight: 600;
            border-radius: 8px;
            padding: 10px 20px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(122, 188, 73, 0.2);
        }

        .btn-search-custom:hover {
            background: linear-gradient(135deg, var(--mantis-2) 0%, var(--mantis) 100%);
            color: var(--white);
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(122, 188, 73, 0.4);
        }

        .btn-search-custom:active {
            transform: translateY(0);
            box-shadow: 0 2px 8px rgba(122, 188, 73, 0.3);
        }

        .btn-outline-secondary {
            border: 2px solid var(--text-muted);
            color: var(--text-muted);
            font-weight: 600;
            border-radius: 8px;
            padding: 8px 20px;
            transition: all 0.3s ease;
        }

        .btn-outline-secondary:hover {
            background-color: var(--text-muted);
            border-color: var(--text-muted);
            color: var(--white);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(108, 117, 125, 0.3);
        }

        .search-input:focus {
            border-color: var(--carolina-blue);
            box-shadow: 0 0 0 0.2rem rgba(100, 173, 212, 0.25);
        }

        .search-table-wrapper {
            border: 1px solid rgba(100, 173, 212, 0.2);
            border-radius: 8px;
            background-color: rgba(100, 173, 212, 0.02);
        }

        .search-table-wrapper .table thead th {
            background-color: rgba(100, 173, 212, 0.08);
            color: var(--text-dark);
            font-weight: 600;
            border-color: rgba(100, 173, 212, 0.15);
        }

        .search-table-wrapper .table tbody tr {
            transition: background-color 0.2s ease;
        }

        .search-table-wrapper .table tbody tr:nth-of-type(odd) {
            background-color: rgba(100, 173, 212, 0.03);
        }

        .search-table-wrapper .table tbody tr:hover {
            background-color: rgba(100, 173, 212, 0.12) !important;
        }

        .search-table-wrapper .table td {
            border-color: rgba(100, 173, 212, 0.15);
        }

        .btn-view-details {
            background: linear-gradient(135deg, var(--carolina-blue) 0%, #5a9ec8 100%);
            color: var(--white);
            border: none;
            font-weight: 600;
            border-radius: 6px;
            padding: 4px 8px;
            font-size: 0.75rem;
            transition: all 0.3s ease;
            box-shadow: 0 2px 6px rgba(100, 173, 212, 0.25);
        }

        .btn-view-details:hover {
            background: linear-gradient(135deg, #5a9ec8 0%, var(--carolina-blue) 100%);
            color: var(--white);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(100, 173, 212, 0.4);
        }

        .btn-reservation {
            background: linear-gradient(135deg, var(--mantis) 0%, var(--mantis-2) 100%);
            color: var(--white);
            border: none;
            font-weight: 600;
            border-radius: 6px;
            padding: 4px 8px;
            font-size: 0.75rem;
            transition: all 0.3s ease;
            box-shadow: 0 2px 6px rgba(122, 188, 73, 0.25);
        }

        .btn-reservation:hover {
            background: linear-gradient(135deg, var(--mantis-2) 0%, var(--mantis) 100%);
            color: var(--white);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(122, 188, 73, 0.4);
        }

        .btn-itinerary {
            background: linear-gradient(135deg, var(--hunyadi-yellow) 0%, #d9a03d 100%);
            color: var(--white);
            border: none;
            font-weight: 600;
            border-radius: 6px;
            padding: 4px 8px;
            font-size: 0.75rem;
            transition: all 0.3s ease;
            box-shadow: 0 2px 6px rgba(231, 179, 78, 0.25);
        }

        .btn-itinerary:hover {
            background: linear-gradient(135deg, #d9a03d 0%, var(--hunyadi-yellow) 100%);
            color: var(--white);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(231, 179, 78, 0.4);
        }
    </style>

    <div class="modal fade w-100" data-bs-backdrop="static" id="tourSearchModal" tabindex="-1" aria-labelledby="tourSearchModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5 mb-2" id="tourSearchModalLabel">
                        <i class="bi bi-search me-2"></i> Search Tours
                    </h1>
                    <button type="button" class="btn-close" aria-label="Close" id="tourSearchModalCloseBtn"></button>
                </div>
                <input type="hidden" id="modal_value">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-10">
                            <input type="text" class="form-control search-input" id="tour_search_input" placeholder="Search by tour ID, manual ID, or consignee name...">
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-search-custom w-100" id="tour_search_btn">
                                <i class="bi bi-search me-1"></i> Search
                            </button>
                        </div>
                    </div>
                    <div class="search-table-wrapper p-3 mt-3">
                        <table class="table table-sm table-hover table-striped align-middle">
                            <thead>
                                <tr>
                                    <th style="width: 5%">#</th>
                                    <th style="width: 10%">ID</th>
                                    <th>Name</th>
                                    <th style="width: 10%">Dates</th>
                                    <th style="width: 10%"></th>
                                    <th style="width: 45%"></th>
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
                    <button type="button" class="btn btn-outline-secondary" id="tourSearchModalCloseBtn">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal" id="loadingModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="loadingModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="width: 300px; border-radius: 12px; border: none; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);">
                <div class="modal-body p-4">
                    <div class="d-flex flex-column align-items-center justify-content-center">
                        <img src="<?php echo base_url() ?>assets/images/loading.gif" width="30px">
                        <label class="form-label mt-2" style="color: var(--text-dark);">Just a moment...</label>
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
        // User privilege URLs from PHP
        const userPrivilegeUrls = <?php echo json_encode($user_privilege_urls ?? []); ?>;

        // Function to check if user has access to a URL
        function hasPrivilege(url) {
            // Check if the URL path matches any privilege URL
            return userPrivilegeUrls.some(privilegeUrl => {
                // Remove base_url and compare the path
                const cleanUrl = url.replace(/^.*?dashboard2\//, 'dashboard2/');
                const cleanPrivilegeUrl = privilegeUrl.replace(/^.*?dashboard2\//, 'dashboard2/');
                return cleanUrl.includes(cleanPrivilegeUrl) || cleanPrivilegeUrl.includes(cleanUrl);
            });
        }

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
                                            ${tour.quotation[0].confirm == 1 ? '<small class="badge badge-success-custom mb-1">Confirmed</small>' : '<small class="badge badge-warning-custom mb-1">Not Confirmed</small>'}
                                            ${tour.quotation[0].finalize == 1 ? '<small class="badge badge-success-custom">Finalized</small>' : '<small class="badge badge-warning-custom">Not Finalized</small>'}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="ms-2">
                                            ${(() => {
                                                if (modalValue == 1) {
                                                    let buttons = '';

                                                    if (hasPrivilege('dashboard2/tour_search/tour_quotation')) {
                                                        buttons += `<a href="<?= base_url('dashboard2/tour_search/tour_quotation?tour_id=') ?>${tour.tour_id}" class="btn btn-view-details btn-sm me-1" target="_blank"><i class="bi bi-eye me-1"></i>View Details</a>`;
                                                    }

                                                    if (hasPrivilege('dashboard2/tour_search/reservation_card')) {
                                                        buttons += ` < a href = "<?= base_url('dashboard2/tour_search/reservation_card?tour_id=') ?>${tour.tour_id}"
                                                        class = "btn btn-reservation btn-sm me-1"
                                                        target = "_blank" > < i class = "bi bi-calendar-check me-1" > < /i>Reservation</a > `;
                                                    }

                                                    if (hasPrivilege('dashboard2/tour_search/itinerary_print')) {
                                                        buttons += ` < a href = "<?= base_url('dashboard2/tour_search/itinerary_print?tour_id=') ?>${tour.tour_id}"
                                                        class = "btn btn-itinerary btn-sm"
                                                        target = "_blank" > < i class = "bi bi-printer me-1" > < /i>Itinerary</a > `;
                                                    }

                                                    return buttons || '<span class="text-muted">No access</span>';
                                                } else if (modalValue == 2) {
                                                    let buttons = '';

                                                    if (hasPrivilege('dashboard2/transport/driver_allow/tour_transport')) {
                                                        buttons += ` < a href = "<?= base_url('dashboard2/transport/driver_allow/tour_transport?tour_id=') ?>${tour.tour_id}"
                                                        class = "btn btn-view-details btn-sm me-1"
                                                        target = "_blank" > < i class = "bi bi-truck me-1" > < /i>Driver Allocation</a > `;
                                                    }

                                                    let subButtons = '';

                                                    if (hasPrivilege('dashboard2/transport/driver_advance')) {
                                                        subButtons += ` < a href = "<?= base_url('dashboard2/transport/driver_advance?tour_id=') ?>${tour.tour_id}"
                                                        class = "btn btn-primary btn-sm"
                                                        target = "_blank" > < i class = "bi bi-cash-stack me-1" > < /i>Advance</a > `;
                                                    }

                                                    if (hasPrivilege('dashboard2/transport/driver_expense')) {
                                                        subButtons += ` < a href = "<?= base_url('dashboard2/transport/driver_expense?tour_id=') ?>${tour.tour_id}"
                                                        class = "btn btn-success btn-sm"
                                                        target = "_blank" > < i class = "bi bi-receipt me-1" > < /i>Expense</a > `;
                                                    }

                                                    if (hasPrivilege('dashboard2/transport/driver_logsheet')) {
                                                        subButtons += ` < a href = "<?= base_url('dashboard2/transport/driver_logsheet?tour_id=') ?>${tour.tour_id}"
                                                        class = "btn btn-info btn-sm"
                                                        target = "_blank" > < i class = "bi bi-clipboard-data me-1" > < /i>Log Sheet</a > `;
                                                    }

                                                    if (hasPrivilege('dashboard2/transport/driver_settlement')) {
                                                        subButtons += ` < a href = "<?= base_url('dashboard2/transport/driver_settlement?tour_id=') ?>${tour.tour_id}"
                                                        class = "btn btn-warning btn-sm"
                                                        target = "_blank" > < i class = "bi bi-check-circle me-1" > < /i>Settlement</a > `;
                                                    }

                                                    if (subButtons) {
                                                        buttons += ` < div class = "d-flex flex-wrap gap-2 mt-2" > $ {
                                                            subButtons
                                                        } < /div>`;
                                                    }

                                                    return buttons || '<span class="text-muted">No access</span>';
                                                }
                                                return '';
                                            })()
                                    } <
                                    /div> < /
                                td > <
                                /tr>
                                `);
                        });
                    } else {
                        toastr.error(response.message || 'No tours found for the given keyword.');
                        tbody.append(` <
                                tr >
                                <
                                td colspan = "4"
                                class = "text-center text-muted" >
                                <
                                i class = "bi bi-search me-2" > < /i> No tours found for the given keyword. < /
                                td > <
                                /tr>
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

        $('#tourSearchModalCloseBtn').on('click', function() {
            location.reload();
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