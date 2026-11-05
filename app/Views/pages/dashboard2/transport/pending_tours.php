<?= view('layouts/head') ?>

<body class="pda-body">
    <style>
        /* tf-pda-r1 (22 Oct 2026): the Avenir Holidays uniform - deep burgundy, deep green, warm gold on neutrals. Presentation only. */
        .pda-body { background:#f7f5f3; }
        .pda { --pda-burgundy:#842484; --pda-burgundy-2:#842484; --pda-green:#78b848; --pda-green-2:#78b848; --pda-gold:#e0a526; --pda-gold-ink:#2b2108;
               --pda-ink:#2b2b33; --pda-muted:#6b6f7b; --pda-line:#e8e3df; --pda-card:#fff; --pda-tint:#faf6f2;
               font-family:"Inter","Segoe UI",system-ui,-apple-system,Roboto,Arial,sans-serif; color:var(--pda-ink); max-width:1400px; margin:0 auto; }
        .pda .pda-head { display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; margin:6px 0 18px; }
        .pda .pda-title { font-size:1.1rem; font-weight:700; letter-spacing:-0.02em; line-height:1.15; color:var(--pda-burgundy); margin:0; }
        .pda .pda-sub { margin:4px 0 0; font-size:.92rem; color:var(--pda-muted); letter-spacing:-0.005em; }
        .pda .pda-sub b { color:var(--pda-ink); font-weight:600; }
        .pda .pda-card { background:var(--pda-card); border:1px solid var(--pda-line); border-radius:14px; box-shadow:0 4px 14px rgba(132,36,132,.06); padding:18px 20px; margin-bottom:16px; }
        .pda .form-label { font-size:.78rem; font-weight:600; letter-spacing:.02em; text-transform:uppercase; color:var(--pda-muted); margin-bottom:6px; }
        .pda .form-control { border-radius:10px; border-color:var(--pda-line); height:40px; font-size:.92rem; }
        .pda .form-control:focus { border-color:rgba(132,36,132,.45); box-shadow:0 0 0 .2rem rgba(132,36,132,.12); }
        .pda .pda-btn { display:inline-flex; align-items:center; justify-content:center; gap:7px; height:40px; padding:0 16px; border-radius:10px; border:1px solid transparent;
               font-size:.88rem; font-weight:600; letter-spacing:.01em; line-height:1; white-space:nowrap; text-decoration:none; cursor:pointer;
               box-shadow:0 3px 10px rgba(0,0,0,.10); transition:filter .2s ease, box-shadow .2s ease, transform .2s ease; }
        .pda .pda-btn:hover { filter:brightness(1.08); box-shadow:0 5px 14px rgba(0,0,0,.16); }
        .pda .pda-btn:active { transform:translateY(1px) scale(.98); }
        .pda .pda-btn:focus-visible { outline:3px solid rgba(224,165,38,.55); outline-offset:2px; }
        .pda .pda-btn-green { background:var(--pda-green); color:#2b2b33; }
        .pda .pda-btn-burgundy { background:var(--pda-burgundy); color:#fff; }
        .pda .pda-btn-gold { background:var(--pda-gold); color:var(--pda-gold-ink); }
        .pda .pda-btn-plain { background:#fff; color:var(--pda-ink); border-color:var(--pda-line); box-shadow:none; }
        .pda .pda-btn-plain:hover { background:var(--pda-tint); filter:none; }
        .pda .pda-btn-sm { height:32px; padding:0 12px; font-size:.8rem; border-radius:9px; }
        .pda .pda-actions { display:flex; gap:10px; justify-content:flex-end; margin-top:14px; }
        .pda .pda-table { margin:0; border-color:var(--pda-line); font-size:.9rem; }
        .pda .pda-table thead th { background:var(--pda-burgundy); color:#fff; border-color:var(--pda-burgundy-2); font-size:.76rem; font-weight:600;
               letter-spacing:.04em; text-transform:uppercase; padding:11px 12px; vertical-align:middle; white-space:nowrap; }
        .pda .pda-table thead th.sorting:before, .pda .pda-table thead th.sorting:after { opacity:.55; }
        .pda .pda-table tbody td { padding:10px 12px; vertical-align:middle; border-color:var(--pda-line); }
        .pda .pda-table.table-striped > tbody > tr:nth-of-type(odd) > * { --bs-table-accent-bg:var(--pda-tint); }
        .pda .pda-table.table-hover > tbody > tr:hover > * { --bs-table-accent-bg:#f3ece6; }
        .pda .pda-id { font-weight:700; color:var(--pda-burgundy); }
        .pda .pda-pax { display:inline-block; min-width:30px; padding:3px 9px; border-radius:20px; background:var(--pda-gold); color:var(--pda-gold-ink); font-weight:700; font-size:.78rem; }
        .pda .pda-empty { color:var(--pda-muted); }
        .pda .pda-empty i { color:#78b848; }
        /* DataTables controls in the same palette */
        .pda .dataTables_wrapper .dataTables_length, .pda .dataTables_wrapper .dataTables_info { font-size:.85rem; color:var(--pda-muted); padding:10px 0; }
        .pda .dataTables_wrapper .dataTables_length select { border-radius:8px; border:1px solid var(--pda-line); padding:3px 6px; }
        .pda .dataTables_wrapper .pagination .page-link { color:var(--pda-burgundy); border-color:var(--pda-line); }
        .pda .dataTables_wrapper .pagination .page-item.active .page-link { background:var(--pda-burgundy); border-color:var(--pda-burgundy); color:#fff; }
        .pda .dataTables_wrapper .dataTables_paginate { padding-top:10px; }
        @media (max-width:767px) {
            .pda .pda-title { font-size:1rem; }
            .pda .pda-card { padding:14px; }
            .pda .pda-actions { justify-content:stretch; } .pda .pda-actions .pda-btn { flex:1; }
        }
    </style>

    <!-- Fixed Header with Logo and Back Button -->
    <?= view('layouts/topbar', ['topbar_back' => base_url('home/transport_desk'), 'topbar_home' => false, 'topbar_logout' => false]) /* tf-transdesk-r1b: Back returns to the Transport Operations Desk */ ?>

    <!-- Main Content Container -->
    <div class="container-fluid p-4 mt-5 pda">
        <?php /* tf-transdesk-r1b (25 Oct 2026): the Transport Operations Desk strip above the page — desk context and navigation only; the page below is unchanged */ ?>
        <?= view('layouts/tourdesk_bar', ['tourdesk_desk_only' => 'transport']) ?>
        <!-- Page Header: title, the count waiting, Refresh -->
        <?php $pdaCount = count($pending_tours); ?>
        <div class="pda-head">
            <div>
                <h2 class="pda-title">Pending Driver Allocation</h2>
                <p class="pda-sub"><b><?= $pdaCount ?></b> <?= $pdaCount === 1 ? 'tour is' : 'tours are' ?> awaiting driver allocation</p>
            </div>
            <button class="pda-btn pda-btn-gold" onclick="location.reload()">
                <i class="bi bi-arrow-clockwise"></i> Refresh
            </button>
        </div>

        <!-- Search Filter Card -->
        <div class="pda-card">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="search_manual_id" class="form-label">Manual ID</label>
                    <input type="text" class="form-control" id="search_manual_id" placeholder="Search by Manual ID">
                </div>
                <div class="col-md-3">
                    <label for="search_tour_name" class="form-label">Tour Name</label>
                    <input type="text" class="form-control" id="search_tour_name" placeholder="Search by Tour Name">
                </div>
                <div class="col-md-3">
                    <label for="search_agent" class="form-label">Agent</label>
                    <input type="text" class="form-control" id="search_agent" placeholder="Search by Agent">
                </div>
                <div class="col-md-3">
                    <label for="search_start_date" class="form-label">Start Date</label>
                    <input type="date" class="form-control" id="search_start_date">
                </div>
            </div>
            <div class="pda-actions">
                <button class="pda-btn pda-btn-plain" onclick="clearFilters()">
                    <i class="bi bi-x-circle"></i> Clear
                </button>
                <button class="pda-btn pda-btn-green" onclick="applyFilters()">
                    <i class="bi bi-search"></i> Search
                </button>
            </div>
        </div>

        <!-- Pending Tours Table Card -->
        <div class="pda-card">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover pda-table" id="pending_tours_table">
                    <thead>
                        <tr>
                            <th style="width: 5%;">#</th>
                            <th style="width: 10%;">Manual ID</th>
                            <th style="width: 20%;">Tour Name</th>
                            <th style="width: 15%;">Agent</th>
                            <th style="width: 10%;">Start Date</th>
                            <th style="width: 10%;">End Date</th>
                            <th style="width: 8%;">Passengers</th>
                            <th style="width: 12%;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pending_tours)): ?>
                            <tr>
                                <td colspan="8" class="text-center pda-empty py-4">
                                    <i class="bi bi-check2-circle fs-3"></i>
                                    <p class="mb-0 mt-2">No tours pending driver allocation</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($pending_tours as $index => $tour): ?>
                                <tr>
                                    <td class="text-center"><?= $index + 1 ?></td>
                                    <td><span class="pda-id"><?= esc($tour['manual_id']) ?></span></td>
                                    <td><?= esc($tour['consignee']) ?></td>
                                    <td><?= esc($tour['agent_name']) ?></td>
                                    <td><?= date('d M Y', strtotime($tour['start_date'])) ?></td>
                                    <td><?= date('d M Y', strtotime($tour['end_date'])) ?></td>
                                    <td class="text-center">
                                        <span class="pda-pax"><?= $tour['passenger_count'] ?></span>
                                    </td>
                                    <td class="text-center">
                                        <a href="<?= base_url('dashboard2/transport/driver_allow/tour_transport?tour_id=' . $tour['tour_id']) ?>"
                                           class="pda-btn pda-btn-burgundy pda-btn-sm">
                                            <i class="bi bi-person-plus"></i> Assign Driver
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- jQuery CDN -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- DataTables CDN -->
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap4.min.js"></script>

    <!-- Initialize DataTable with Search Functionality -->
    <script>
        let table;

        $(document).ready(function() {
            <?php if (!empty($pending_tours)): ?>
                table = $('#pending_tours_table').DataTable({
                    "order": [[4, "asc"]], // Sort by start date ascending
                    "pageLength": 25,
                    "language": {
                        "emptyTable": "No tours pending driver allocation"
                    },
                    "dom": 'lrtip' // Remove default search box (we have custom filters)
                });

                // Add live search on input fields
                $('#search_manual_id').on('keyup', function() {
                    table.column(1).search(this.value).draw();
                });

                $('#search_tour_name').on('keyup', function() {
                    table.column(2).search(this.value).draw();
                });

                $('#search_agent').on('keyup', function() {
                    table.column(3).search(this.value).draw();
                });

                $('#search_start_date').on('change', function() {
                    const searchDate = this.value;
                    if (searchDate) {
                        // Convert YYYY-MM-DD to "DD Mon YYYY" format to match table
                        const date = new Date(searchDate);
                        const formatted = date.toLocaleDateString('en-GB', {
                            day: '2-digit',
                            month: 'short',
                            year: 'numeric'
                        });
                        table.column(4).search(formatted).draw();
                    } else {
                        table.column(4).search('').draw();
                    }
                });
            <?php endif; ?>
        });

        function applyFilters() {
            // Trigger search on all fields (live search already working)
            if (table) {
                table.draw();
            }
        }

        function clearFilters() {
            $('#search_manual_id').val('');
            $('#search_tour_name').val('');
            $('#search_agent').val('');
            $('#search_start_date').val('');

            // Clear all column searches
            if (table) {
                table.columns().search('').draw();
            }
        }
    </script>
</body>

</html>
