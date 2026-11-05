<?= view('layouts/head') ?>

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
                                        <h4 class="p-title">Consignees Report</h4>
                                    </div>
                                </div>

                                <?php
                                // The agent mark: which of these customers is a booking agent (customers.agent = 1).
                                // The list holds agents and ticketing customers alike; the quotation screens offer the
                                // marked ones. Mark / Unmark posts to master_files/customer/agent — the same privilege
                                // as this screen — and every change is written to the Tour Desk history.
                                $agents_marked = (int) ($agents_marked ?? 0);
                                $cust_total = count($customers);
                                $quoted_unmarked = 0;
                                foreach ($customers as $c) {
                                    if ((int) ($c['agent'] ?? 0) !== 1 && (int) ($c['quoted'] ?? 0) > 0) {
                                        $quoted_unmarked++;
                                    }
                                }
                                ?>
                                <div class="row clearfix mt-3">
                                    <div class="col-md-12">
                                        <div class="card p-4">
                                            <div class="body">
                                                <div class="d-flex flex-wrap align-items-center gap-2 mb-3" id="agent_bar">
                                                    <span class="text-muted me-1">Show:</span>
                                                    <div class="btn-group btn-group-sm" role="group" aria-label="Show">
                                                        <button type="button" class="btn btn-outline-secondary active agent-filter" data-filter="all">All <span class="badge bg-secondary" id="cnt_all"><?= $cust_total ?></span></button>
                                                        <button type="button" class="btn btn-outline-secondary agent-filter" data-filter="agent">Agents <span class="badge bg-success" id="cnt_agent"><?= $agents_marked ?></span></button>
                                                        <button type="button" class="btn btn-outline-secondary agent-filter" data-filter="other">Not marked <span class="badge bg-light text-dark" id="cnt_other"><?= $cust_total - $agents_marked ?></span></button>
                                                    </div>
                                                    <button type="button" class="btn btn-sm btn-outline-success ms-auto" id="btn_mark_quoted" <?= $quoted_unmarked > 0 ? '' : 'hidden' ?>>Mark the <span id="cnt_quoted_unmarked"><?= $quoted_unmarked ?></span> on quotations as agents</button>
                                                </div>
                                                <div class="text-muted small mb-3" id="agent_note">
                                                    <?php if ($agents_marked === 0) { ?>
                                                        No customer is marked as an agent yet, so the quotation screens still list everyone. Press <b>Mark as agent</b> on each booking agent; ticketing customers stay unmarked.
                                                    <?php } else { ?>
                                                        The <b><?= $agents_marked ?></b> customer<?= $agents_marked === 1 ? '' : 's' ?> marked as agents are what the quotation screens offer; the rest can still be reached there under "show all customers". "on N quotations" counts the quotations and inquiries a customer has been the agent on.
                                                    <?php } ?>
                                                </div>
                                                <div class="table-responsive">
                                                    <table class="table table-hover js-basic-example dataTable table-custom" id="mainTable">
                                                        <thead class="thead-dark">
                                                            <tr>
                                                                <th>#</th>
                                                                <th>Name</th>
                                                                <th>Country</th>
                                                                <th>Address</th>
                                                                <th>Margin</th>
                                                                <th>Agent</th>
                                                                <th>Action</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <!-- itertate through $admin array -->
                                                            <?php
                                                            $x = 1;
                                                            foreach ($customers as $customer) {
                                                            ?>
                                                                <?php $is_agent = (int) ($customer['agent'] ?? 0) === 1 ? 1 : 0; $n_quoted = (int) ($customer['quoted'] ?? 0); ?>
                                                                <tr style="width: 100%;" class="cust-row" data-id="<?= (int) $customer['ID'] ?>" data-agent="<?= $is_agent ?>" data-quoted="<?= $n_quoted ?>">
                                                                    <td style="width: 5%;"><?php echo $x; ?></td>
                                                                    <td>
                                                                        <div class="d-flex flex-column">
                                                                            <strong><?php echo $customer['Name']; ?></strong>
                                                                            <?php if ($n_quoted > 0) { ?><small class="text-muted">on <?= $n_quoted ?> quotation<?= $n_quoted === 1 ? '' : 's' ?></small><?php } ?>
                                                                        </div>
                                                                    </td>
                                                                    <td style="width: 15%;">
                                                                        <img src="https://flagcdn.com/16x12/<?= strtolower($customer['country']); ?>.png" width="16" height="12" alt="<?= $customer['country']; ?>">
                                                                        <span class="ms-2"><?= $customer['nicename']; ?></span>
                                                                    </td>
                                                                    <td style="width: 20%;"><?php echo $customer['Address']; ?></td>
                                                                    <td style="width: 10%;"><?php echo $customer['margin']; ?></td>
                                                                    <td style="width: 14%; white-space: nowrap;" class="agent-cell">
                                                                        <?php if ($is_agent) { ?>
                                                                            <span class="badge bg-success me-1">Agent</span>
                                                                            <button type="button" class="btn btn-sm btn-light agent-toggle" data-to="0" title="Take the agent mark off this customer">Unmark</button>
                                                                        <?php } else { ?>
                                                                            <button type="button" class="btn btn-sm btn-outline-success agent-toggle" data-to="1" title="Mark this customer as a booking agent">Mark as agent</button>
                                                                        <?php } ?>
                                                                    </td>
                                                                    <td style="width: 5%;">
                                                                        <div class="dropup">
                                                                            <button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                                                <i class="bi bi-three-dots-vertical"></i>
                                                                            </button>
                                                                            <ul class="dropdown-menu" style="z-index: 99999;">
                                                                                <li>
                                                                                    <a href="<?php echo base_url('master_files/customer?id=' . $customer['ID']) ?>" class="dropdown-item edit-menu-item-btn" data-menu-item-id="<?php echo $customer['ID']; ?>">Edit</a>
                                                                                </li>
                                                                                <li>
                                                                                    <a class="dropdown-item text-danger delete-menu-item-btn" href="#" data-menu-item-id="<?php echo $customer['Sup_Acc_No']; ?>">Delete</a>
                                                                                </li>
                                                                            </ul>
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                            <?php
                                                                $x++;
                                                            }
                                                            ?>
                                                        </tbody>
                                                    </table>
                                                </div>
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
    <script src="<?= base_url(); ?>assets/bundles/datatablescripts.bundle.js"></script>
    <script src="<?= base_url(); ?>assets/vendor/jquery-datatable/buttons/dataTables.buttons.min.js"></script>
    <script src="<?= base_url(); ?>assets/vendor/jquery-datatable/buttons/buttons.bootstrap4.min.js"></script>
    <script src="<?= base_url(); ?>assets/vendor/jquery-datatable/buttons/buttons.colVis.min.js"></script>
    <script src="<?= base_url(); ?>assets/vendor/jquery-datatable/buttons/buttons.html5.min.js"></script>
    <script src="<?= base_url(); ?>assets/vendor/jquery-datatable/buttons/buttons.print.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/vfs_fonts.js"></script>

    <script>
        $('.js-example-basic-single').select2({
            placeholder: " Select",
            allowClear: true
        });

        var report_title = 'Consignees Report';

        // Show: All / Agents / Not marked — a filter on the row's own agent mark, so it stays
        // right after a mark changes without reloading the page.
        var agentFilter = 'all';
        $.fn.dataTable.ext.search.push(function(settings, data, dataIndex, rowData, counter) {
            if (settings.nTable.id !== 'mainTable' || agentFilter === 'all') return true;
            var row = settings.aoData[dataIndex] && settings.aoData[dataIndex].nTr;
            var isAgent = row && row.getAttribute('data-agent') === '1';
            return agentFilter === 'agent' ? isAgent : !isAgent;
        });

        window.custTable = $('#mainTable').DataTable({
            "aaSorting": [],

            rowReorder: {
                selector: 'td:nth-child(2)'
            },
            responsive: 'true',
            dom: "Bfrtip",
            buttons: [{
                    extend: 'csvHtml5',
                    text: 'CSV',
                    titleAttr: 'CSV',
                    title: report_title,
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4]
                    }
                },
                {
                    extend: 'pdfHtml5',
                    text: 'PDF',
                    titleAttr: 'PDF',
                    title: report_title,
                    orientation: 'portrait', // Set orientation to landscape
                    pageSize: 'A4', // Set page size to A4
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4]
                    },
                    customize: function(win) {}
                },
                {
                    extend: 'print',
                    text: 'Print',
                    titleAttr: 'Print',
                    title: report_title,
                    pageSize: 'A4',
                    orientation: 'portrait',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4]
                    }

                },
            ]
        });
    </script>
    <script>
        function isNumberKey(evt, obj) {
            var charCode = (evt.which) ? evt.which : event.keyCode
            var value = obj.value;
            var dotcontains = value.indexOf(".") != -1;
            if (dotcontains)
                if (charCode == 46) return false;
            if (charCode == 46) return true;
            if (charCode > 31 && (charCode < 48 || charCode > 57))
                return false;
            return true;
        }

        $(function() {
            $(':input[required=""],:input[required]').bind('focusout', function() {
                if ($(this).val() == "") {
                    $(this).css("border-color", "red");
                } else {
                    $(this).css("border-color", "#ccc");
                }
            });

            $('.txt_nav:first').focus();
            var $inp = $('.txt_nav');
            $inp.bind('keydown', function(e) {
                var n = $inp.length;
                var key = e.which;
                if (key == 13) {
                    e.preventDefault();
                    var nxtIdx = $inp.index(this) + 1;
                    if (nxtIdx < n) {
                        $('.txt_nav')[nxtIdx].focus();
                    } else {
                        $('.txt_nav')[nxtIdx - 1].blur();
                        $('#submit_btn').focus();
                    }
                }
            });


            $("#submit_btn").keypress(function(event) {
                if (event.which == 13) {
                    req_input_check();
                }
            });

        });

        function req_input_check() {
            $(':input[required=""],:input[required]').bind('keypress', function(event) {
                if (event.which == 13) {
                    if ($(this).val() == "") {
                        $(this).css("border-color", "red");
                    } else {
                        $(this).css("border-color", "#ccc");
                        $('#form_submit').submit();
                    }
                }
            });
        }

        $('#exampleCheck').change(function() {
            if ($(this).is(':checked')) {
                $("#Services").removeAttr("hidden");
                $("#Services1").removeAttr("hidden");
            } else {
                $("#Services").attr('hidden', true);
                $("#Services1").attr('hidden', true);
            }
        });

        /**************************** INSERT  ****************************/

        $("#form_submit").on('submit', (function(e) {
            e.preventDefault();
            $.ajax({
                url: "<?php echo base_url() ?>master_files/supplier/create",
                type: "POST",
                data: new FormData(this),
                processData: false,
                contentType: false,
                cache: false,
                dataType: 'json',
                success: function(response) {
                    console.log(response);
                    if (response.status == 201) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: response.message,
                        }).then((result) => {
                            if (result.isConfirmed) {
                                location.reload();
                            }
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message,
                        });
                    }
                },
                error: function(e) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: e.responseJSON.message ? e.responseJSON.message : 'An error occurred!',
                    });
                }
            });
        }));

        /**************************** AGENT MARK  ****************************/

        // A small confirmation in the corner, so marking thirty agents in a row is thirty clicks, not sixty.
        var agentToast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 2200, timerProgressBar: true });

        function agentCounts() {
            var rows = $('#mainTable tbody tr.cust-row');
            var agents = rows.filter('[data-agent="1"]').length;
            var quotedUnmarked = rows.filter('[data-agent="0"]').filter(function() { return parseInt(this.getAttribute('data-quoted') || '0', 10) > 0; }).length;
            $('#cnt_all').text(rows.length);
            $('#cnt_agent').text(agents);
            $('#cnt_other').text(rows.length - agents);
            $('#cnt_quoted_unmarked').text(quotedUnmarked);
            $('#btn_mark_quoted').prop('hidden', quotedUnmarked === 0);
            $('#agent_note').html(agents === 0
                ? 'No customer is marked as an agent yet, so the quotation screens still list everyone. Press <b>Mark as agent</b> on each booking agent; ticketing customers stay unmarked.'
                : 'The <b>' + agents + '</b> customer' + (agents === 1 ? '' : 's') + ' marked as agents are what the quotation screens offer; the rest can still be reached there under "show all customers". "on N quotations" counts the quotations and inquiries a customer has been the agent on.');
        }

        // Redraw one row's Agent cell from its mark, and keep the DataTable's copy of the row in step.
        function paintAgentCell(tr, isAgent) {
            tr.attr('data-agent', isAgent ? '1' : '0');
            tr.find('td.agent-cell').html(isAgent
                ? '<span class="badge bg-success me-1">Agent</span><button type="button" class="btn btn-sm btn-light agent-toggle" data-to="0" title="Take the agent mark off this customer">Unmark</button>'
                : '<button type="button" class="btn btn-sm btn-outline-success agent-toggle" data-to="1" title="Mark this customer as a booking agent">Mark as agent</button>');
            if (window.custTable) custTable.row(tr).invalidate();
        }
        function redrawCustomers(keepPage) {
            if (window.custTable) custTable.draw(keepPage === undefined ? undefined : keepPage);
        }

        $('#mainTable').on('click', '.agent-toggle', function() {
            var btn = $(this);
            var tr = btn.closest('tr');
            var to = btn.data('to') == 1 ? 1 : 0;
            btn.prop('disabled', true);
            $.ajax({
                url: "<?php echo base_url() ?>master_files/customer/agent",
                type: "POST",
                data: { id: tr.data('id'), agent: to },
                dataType: 'json',
                success: function(response) {
                    if (response.status == 200) {
                        paintAgentCell(tr, response.agent == 1);
                        agentCounts();
                        redrawCustomers(false);
                        agentToast.fire({ icon: 'success', title: response.message });
                    } else {
                        btn.prop('disabled', false);
                        Swal.fire({ icon: 'error', title: 'Error', text: response.message });
                    }
                },
                error: function(e) {
                    btn.prop('disabled', false);
                    Swal.fire({ icon: 'error', title: 'Error', text: (e.responseJSON && e.responseJSON.message) ? e.responseJSON.message : 'The mark could not be saved (are you still logged in?)' });
                }
            });
        });

        $('.agent-filter').on('click', function() {
            $('.agent-filter').removeClass('active');
            $(this).addClass('active');
            agentFilter = $(this).data('filter');
            redrawCustomers();
        });

        // Every customer already on a quotation or an inquiry, marked in one go — what the
        // quotation records say, nothing guessed; each mark can be taken off again one by one.
        $('#btn_mark_quoted').on('click', function() {
            var n = $('#cnt_quoted_unmarked').text();
            Swal.fire({
                title: 'Mark ' + n + ' customer(s) as agents?',
                text: 'These customers have been the agent on at least one quotation or inquiry and are not marked yet. Each mark can be taken off again.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, mark them'
            }).then(function(result) {
                if (!result.isConfirmed) return;
                $.ajax({
                    url: "<?php echo base_url() ?>master_files/customer/agent_quoted",
                    type: "POST",
                    dataType: 'json',
                    success: function(response) {
                        if (response.status == 200) {
                            (response.marked || []).forEach(function(m) {
                                paintAgentCell($('#mainTable tbody tr.cust-row[data-id="' + m.id + '"]'), true);
                            });
                            agentCounts();
                            redrawCustomers(false);
                            Swal.fire({ icon: 'success', title: 'Done', text: response.message });
                        } else {
                            Swal.fire({ icon: 'error', title: 'Error', text: response.message });
                        }
                    },
                    error: function(e) {
                        Swal.fire({ icon: 'error', title: 'Error', text: (e.responseJSON && e.responseJSON.message) ? e.responseJSON.message : 'The marks could not be saved (are you still logged in?)' });
                    }
                });
            });
        });

        // .delete-menu-item-btn click event
        $('.delete-menu-item-btn').click(function() {
            var acc_no = $(this).data('menu-item-id');
            // alert(acc_no);
            Swal.fire({
                title: 'Are you sure?',
                text: "You want to delete this Consignee!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "<?php echo base_url() ?>master_files/customer/delete",
                        type: "GET",
                        data: {
                            Acc_No: acc_no
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.status == 200) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success',
                                    text: response.message,
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        location.reload();
                                    }
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: response.message,
                                });
                            }
                        },
                        error: function(e) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: e.responseJSON.message ? e.responseJSON.message : 'An error occurred!',
                            });
                        }
                    });
                }
            });
        });
    </script>
<script>
/* tf-stage3 — a ?q= in the address (from the Master Files search) pre-fills the table search */
(function () {
    var q = new URLSearchParams(location.search).get('q') || '';
    if (!q || !window.jQuery) return;
    var tries = 0, t = setInterval(function () {
        var $t = jQuery('#mainTable');
        if ($t.length && jQuery.fn.dataTable && jQuery.fn.dataTable.isDataTable($t[0])) { clearInterval(t); $t.DataTable().search(q).draw(); }
        else if (++tries > 50) { clearInterval(t); }
    }, 100);
})();
</script>
</body>




</html>