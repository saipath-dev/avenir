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
                                    <h4 class="p-title">Tour Monthly Dashboard</h4>
                                </div>
                            </div>


                            <div class="row clearfix mt-3">
                                <div class="col-md-12">
                                    <div class="card p-4">
                                        <div class="body">
                                            <div class="table-responsive">
                                                <table class="table js-basic-example table-hover  table-custom align-middle">
                                                    <thead class="thead-dark">
                                                    <tr>
                                                        <th rowspan="3" class="text-center align-middle">Tour ID</th>
                                                        <th rowspan="3" class="text-center align-middle">Manual ID</th>
                                                        <th rowspan="3" class="text-center align-middle">Agent
                                                            Reference
                                                        </th>
                                                        <th rowspan="3" class="text-center align-middle">Tour
                                                            Details<br>
                                                            (S.Date, T.Name, T.C.User)
                                                        </th>
                                                        <th rowspan="3" class="text-center align-middle">Passengers</th>

                                                        <th colspan="8" class="text-center">Status Summary</th>
                                                        <th rowspan="3" class="text-center align-middle">Action</th>
                                                    </tr>
                                                    <tr>
                                                        <th colspan="6" class="text-center">Pre Status</th>
                                                        <th colspan="2" class="text-center">Post Status</th>
                                                    </tr>
                                                    <tr>
                                                        <th>Reservation Card</th>
                                                        <th>Itinerary Print</th>
                                                        <th>Booking</th>
                                                        <th>Invoice</th>
                                                        <!-- <th>Add Invoice</th> -->
                                                        <!-- <th>Additional INV</th> -->
                                                        <th>Hotel Reservation</th>
                                                        <th>Transport</th>
                                                        <th>Tour Expenses</th>
                                                        <th>Receipting</th>

                                                    </tr>

                                                    </thead>
                                                    <tbody>
                                                    <?php
                                                    if (isset($tour_details) && !empty($tour_details)) {
                                                        foreach ($tour_details as $row) {

                                                            if ($row->Finalize != 1) {
                                                                $bg = 'background-color: #d648f03d;';
                                                            } else {
                                                                $bg = '';
                                                            }

                                                            $hotel_only = $row->hotel_only;
                                                            $transport_only = $row->transport_only;

                                                            $total_doller = $row->total_hotel_doller;
                                                            $total_paid_doller = $row->pay_doller_count;

                                                            // $balace_doller = $total_doller - $total_paid_doller;
                                                            ?>
                                                            <tr style="<?= $bg; ?>">
                                                                <td>
                                                                    <?= $row->tour_id; ?>
                                                                </td>
                                                                <td>
                                                                    <?php if (isset($row->manual_id) && !empty($row->manual_id)) { ?>
                                                                        <div class="manual-id-button">
                                                                            <?= $row->manual_id; ?>
                                                                        </div>
                                                                        <div class="manual-id-button">
                                                                            <button type="button"
                                                                                    class="btn btn-success btn-sm manual_id_edit"
                                                                                    title="Edit Manual ID"
                                                                                    onClick='addManualID("<?= $row->tour_id; ?>")'
                                                                                    data-bs-toggle='modal'
                                                                                    data-bs-target='#manualIdModal'
                                                                                    data-tour="<?= $row->tour_id; ?>"
                                                                                    data-edit="1"><i
                                                                                        class="bi bi-pencil"></i>
                                                                            </button>
                                                                        </div>
                                                                    <?php } else { ?>
                                                                        <button type="button"
                                                                                class="btn btn-success btn-sm manual_id"
                                                                                title="Add Manual ID"
                                                                                onClick='addManualID("<?= $row->tour_id; ?>")'
                                                                                data-bs-toggle='modal'
                                                                                data-bs-target='#manualIdModal'><i
                                                                                    class="bi bi-plus-circle"></i>
                                                                        </button>
                                                                    <?php } ?>
                                                                </td>
                                                                <td>
                                                                    <?php if (isset($row->agent_ref) && !empty($row->agent_ref)) { ?>
                                                                        <div class="manual-id-button">
                                                                            <?= $row->agent_ref; ?>
                                                                        </div>
                                                                        <div class="manual-id-button">
                                                                            <button type="button"
                                                                                    class="btn btn-success btn-sm agent_ref_edit"
                                                                                    title="Edit Agent Reference"
                                                                                    onClick='addagentRefD("<?= $row->tour_id; ?>")'
                                                                                    data-bs-toggle='modal'
                                                                                    data-bs-target='#agentrefModal'
                                                                                    data-tour="<?= $row->tour_id; ?>"
                                                                                    data-edit="1"><i
                                                                                        class="bi bi-pencil"></i>
                                                                            </button>
                                                                        </div>
                                                                    <?php } else { ?>
                                                                        <button type="button"
                                                                                class="btn btn-success btn-sm agent_ref"
                                                                                title="Add Agent Reference"
                                                                                onClick='addagentRefD("<?= $row->tour_id; ?>")'
                                                                                data-bs-toggle='modal'
                                                                                data-bs-target='#agentrefModal'><i
                                                                                    class="bi bi-plus-circle"></i>
                                                                        </button>
                                                                    <?php } ?>
                                                                </td>
                                                                <td class="text-secondary">
                                                                    <?= date('d-F Y', strtotime($row->start_date)) . '&nbsp;&nbsp;'; ?>
                                                                    <br>
                                                                    <code><?= $row->consignee; ?></code><br>
                                                                    <code style="color: green;"><?= $row->create_user; ?></code>

                                                                </td>

                                                                <td class="text-secondary">
                                                                    <?php
                                                                    foreach ($differecePassengerCount as $value) {
                                                                        if ($value['tour_id'] == $row->tour_id) {

                                                                            if ($value['count'] == $row->pax_count) {
                                                                                $pax_names = array();
                                                                                foreach ($pax_details as $row1) {
                                                                                    if ($row1['tour_id'] == $row->tour_id) {
                                                                                        $pax_names[] = $row1['pass_name'];
                                                                                    }
                                                                                }
                                                                                echo implode('&nbsp;&nbsp;, &nbsp;&nbsp;&nbsp;', $pax_names);
                                                                            } else {
                                                                                // $pass_count = $row->pax_count;
                                                                                // echo '<button type="button" class="btn btn-soft-warning btn-sm" title="Add Passenger" data-bs-toggle="modal" data-bs-target="#AddmemberModal" onClick="addPassenger('. $value['tour_id'] .','. $pass_count .')"><i class="bi bi-plus-circle-fill"></i></button>';
                                                                            }
                                                                        }
                                                                    }
                                                                    ?>
                                                                </td>

                                                                <td>
                                                                    <div class="d-flex align-items-center">
                                                                        <?php if (isset($row->Finalize) && $row->Finalize == 1) { ?>
                                                                            <a href="<?= base_url('tour/print/print_quotation_with_amounts?tour_id=' . $row->tour_id); ?>"
                                                                               class="btn btn-dark btn-sm me-2"
                                                                               target="_blank" data-toggle="tooltip"
                                                                               title="Reservation Card Print"><i
                                                                                        class="bi bi-printer"></i></a>
                                                                            <a href="<?= base_url('tour/print?tour_id=' . $row->tour_id); ?>"
                                                                               class="btn btn-info btn-sm"
                                                                               target="_blank" data-toggle="tooltip"
                                                                               title="Print Without Amounts"><i
                                                                                        class="bi bi-printer"></i></a>
                                                                        <?php } else { ?>
                                                                            <img src="<?php echo base_url(); ?>assets/images/close.png"
                                                                                 alt="..." width="20px" class="me-2">
                                                                        <?php } ?>
                                                                    </div>
                                                                </td>
                                                                <td>
                                                                    <div class="d-flex align-items-center">

                                                                        <a href="<?= base_url('tour/print/itinerary?tour_id=' . $row->tour_id); ?>"
                                                                           class="btn btn-soft-success btn-sm"
                                                                           target="_blank" data-toggle="tooltip"
                                                                           title="Itinerary Print"><i
                                                                                    class="bi bi-printer-fill"></i></a>

                                                                    </div>
                                                                </td>
                                                                <td>
                                                                    <div class="d-flex align-items-center">

                                                                        <?php if (isset($row->Confirm) && $row->Confirm == 3 && isset($row->Finalize) && $row->Finalize == 1) { ?>

                                                                            <button type="button"
                                                                                    class="btn btn-danger btn-sm"
                                                                                    title="Booking Status"
                                                                                    data-tour="<?= $row->tour_id; ?>">
                                                                                Tour Cancelled
                                                                            </button>

                                                                        <?php } else if (isset($row->Finalize) && $row->Finalize == 1 && isset($row->Confirm) && $row->Confirm == 1) { ?>

                                                                            <img src="<?php echo base_url(); ?>assets/images/ok.png"
                                                                                 alt="..." width="20px" class="me-2">

                                                                        <?php } else if (isset($row->Finalize) && $row->Finalize != 1 && isset($row->Confirm) && $row->Confirm == 1) { ?>

                                                                            <img src="<?php echo base_url(); ?>assets/images/close.png"
                                                                                 alt="..." width="20px" class="me-2">

                                                                        <?php } else if (isset($row->Finalize) && $row->Finalize != 1 && isset($row->Confirm) && $row->Confirm != 1) { ?>

                                                                            <img src="<?php echo base_url(); ?>assets/images/close.png"
                                                                                 alt="..." width="20px" class="me-2">

                                                                        <?php } ?>
                                                                    </div>
                                                                </td>
                                                                <td>
                                                                    <div class="d-flex align-items-center">
                                                                        <div class="d-flex align-items-center">
                                                                            <img src="<?php echo base_url(); ?>assets/images/<?php echo (isset($row->Confirm) && $row->Confirm == 1) ? "ok" : "close"; ?>.png"
                                                                                 alt="..." width="20px"
                                                                                 class="me-2">
                                                                            <?php if (isset($row->Confirm) && $row->Confirm == 1) { ?>
                                                                                <button class="btn btn-primary btn-sm invoice_print me-2"
                                                                                        title="Invoice Reprint"
                                                                                        data-tour="<?= $row->tour_id; ?>">
                                                                                    <i class="bi bi-printer"></i>
                                                                                </button>
                                                                            <?php } ?>

                                                                            <?php if (isset($row->Confirm) && $row->Confirm == 1) { ?>
                                                                                <button class="btn btn-primary btn-sm open_em_modal"
                                                                                        title="Send Invoice"
                                                                                        data-tour="<?= $row->tour_id; ?>">
                                                                                    <i class="bi bi-send ml-2"></i>
                                                                                </button>
                                                                            <?php } ?>

                                                                        </div>

                                                                    </div>
                                                                </td>
                                                                <!-- <td>
                                                                            <div class="d-flex align-items-center">

                                                                                <button type="button" class="btn btn-info btn-sm tour_additional_inv" title="Additional Invoice" data-tour="<?//= $row->tour_id; ?>">Add INV</button>

                                                                            </div>
                                                                        </td> -->
                                                                <!-- <td>
                                                                            <div class="d-flex align-items-center">

                                                                                <button class="btn btn-primary btn-sm invoice_print" title="Invoice Reprint" data-tour="<?//= $row->tour_id; ?>"><i class="bi bi-printer"></i></button>
                                                                            </div>
                                                                        </td> -->
                                                                <td>
                                                                    <div class="d-flex align-items-center">
                                                                        <?php
                                                                        if ($transport_only == 1 || $row->Confirm == 3) {
                                                                        } else {
                                                                            $set_flag_booking = $set_flag_confirm = $set_flag_payment = 0;


                                                                            $ok_img = '<img src="' . base_url() . 'assets/images/ok.png" alt="..." width="20px" class="me-2">';

                                                                            if (isset($row->quotation_days)) {
                                                                                if (isset($row->hotel_booking_cnt) && $row->quotation_days == $row->hotel_booking_cnt) {
                                                                                    $set_flag_booking = 1;
                                                                                    // echo $ok_img;
                                                                                    echo '<button class="btn btn-sm btn-success me-2" onclick="hotelReserve( ' . $row->tour_id . ',\' ' . addslashes($row->start_date) . '\',\' ' . addslashes($row->end_date) . '\', 1)"><i class="bi bi-check-circle"></i></button>';
                                                                                } else {
                                                                                    $set_flag_booking = 0;
                                                                                    echo '<button class="btn btn-sm btn-danger me-2" onclick="hotelReserve( ' . $row->tour_id . ',\' ' . addslashes($row->start_date) . '\',\' ' . addslashes($row->end_date) . '\', 1)"><i class="bi bi-x-circle" title="Hotel Booking Not Completed" ></i></button>';
                                                                                }

                                                                                if (isset($row->booking_confirmed_cnt) && $row->quotation_days == $row->booking_confirmed_cnt) {
                                                                                    $set_flag_confirm = 1;
                                                                                    // echo $ok_img;
                                                                                    echo '<button class="btn btn-sm btn-success me-2" onclick="hotelReserve( ' . $row->tour_id . ',\' ' . addslashes($row->start_date) . '\',\' ' . addslashes($row->end_date) . '\', 2)"><i class="bi bi-check-circle"></i></button>';
                                                                                } else {
                                                                                    $set_flag_confirm = 0;
                                                                                    echo '<button class="btn btn-sm btn-danger me-2" onclick="hotelReserve(' . $row->tour_id . ', \' ' . addslashes($row->start_date) . '\',\' ' . addslashes($row->end_date) . '\', 2)"><i class="bi bi-x-circle" title="Booking Confirmed Not Completed" ></i></button>';
                                                                                }

                                                                                if (isset($row->hotel_payment_cnt) && $row->quotation_days == $row->hotel_payment_cnt) {
                                                                                    $set_flag_payment = 1;
                                                                                    // echo $ok_img;
                                                                                    if ($total_doller == $total_paid_doller) {
                                                                                        echo '<button class="btn btn-sm btn-success me-2" onclick="hotelReserve( ' . $row->tour_id . ',\' ' . addslashes($row->start_date) . '\',\' ' . addslashes($row->end_date) . '\', 3)"><i class="bi bi-check-circle"></i></button>';
                                                                                    } else {
                                                                                        echo '<button class="btn btn-sm btn-danger me-2" onclick="hotelReserve( ' . $row->tour_id . ',\' ' . addslashes($row->start_date) . '\',\' ' . addslashes($row->end_date) . '\', 3)"><i class="bi bi-x-circle" title="Hotel Payment Not Completed"></i></button>';
                                                                                    }
                                                                                } else {
                                                                                    $set_flag_payment = 0;

                                                                                    echo '<button class="btn btn-sm btn-danger me-2" onclick="hotelReserve(' . $row->tour_id . ' ,\' ' . addslashes($row->start_date) . '\',\' ' . addslashes($row->end_date) . '\', 3)"><i class="bi bi-x-circle" title="Hotel Payment Not Completed"></i></button>';
                                                                                }
                                                                            }
                                                                        }
                                                                        ?>

                                                                    </div>
                                                                </td>
                                                                <td>
                                                                    <div class="d-flex align-items-center">
                                                                        <?php
                                                                        if ($hotel_only == 1 || $row->Confirm == 3) {
                                                                        } else {
                                                                            $trns_advance = $trns_expenses = $trns_settled = 0;

                                                                            if (isset($row->transport_advance) && $row->transport_advance == 1) {
                                                                                $trns_advance = 1;
                                                                                echo '<button class="btn btn-sm btn-soft-success me-2" onclick="transportPayment(' .
                                                                                    $row->tour_id . ', \'' . addslashes($row->start_date) . '\', \'' .
                                                                                    addslashes($row->end_date) . '\', \'' .
                                                                                    addslashes($row->consignee) . '\', ' .
                                                                                    $row->pax_count . ', 1)"><i class="bi bi-check-circle-fill"></i></button>';
                                                                                // echo $ok_img;
                                                                            } else {
                                                                                $trns_advance = 0;
                                                                                echo '<button class="btn btn-sm btn-soft-danger me-2" onclick="transportPayment(' .
                                                                                    $row->tour_id . ', \'' . addslashes($row->start_date) . '\', \'' .
                                                                                    addslashes($row->end_date) . '\', \'' .
                                                                                    addslashes($row->consignee) . '\', ' .
                                                                                    $row->pax_count . ', 1)"><i class="bi bi-x-circle-fill" title="Transport Advance Not Completed"></i></button>';
                                                                            }

                                                                            // if (isset($row->transport_advance) && $row->transport_advance == 1) {
                                                                            //     $trns_advance = 1;
                                                                            //     echo $ok_img;
                                                                            // } else {
                                                                            $trns_expenses = 0;
                                                                            echo '<button class="btn btn-sm btn-soft-info me-2" onclick="transportPayment(' .
                                                                                $row->tour_id . ', \'' . addslashes($row->start_date) . '\', \'' .
                                                                                addslashes($row->end_date) . '\', \'' .
                                                                                addslashes($row->consignee) . '\', ' .
                                                                                $row->pax_count . ', 2)"><i class="bi bi-check-circle-fill" title="Transport Expenses Add"></i></button>';
                                                                            // }

                                                                            if (isset($row->transport_settled) && $row->transport_settled == 1) {
                                                                                $trns_settled = 1;
                                                                                echo '<button class="btn btn-sm btn-soft-success me-2" onclick="transportPayment(' .
                                                                                    $row->tour_id . ', \'' . addslashes($row->start_date) . '\', \'' .
                                                                                    addslashes($row->end_date) . '\', \'' .
                                                                                    addslashes($row->consignee) . '\', ' .
                                                                                    $row->pax_count . ', 3)"><i class="bi bi-check-circle-fill"></i></button>';
                                                                            } else {
                                                                                $trns_settled = 0;
                                                                                echo '<button class="btn btn-sm btn-soft-danger me-2" onclick="transportPayment(' .
                                                                                    $row->tour_id . ', \'' . addslashes($row->start_date) . '\', \'' .
                                                                                    addslashes($row->end_date) . '\', \'' .
                                                                                    addslashes($row->consignee) . '\', ' .
                                                                                    $row->pax_count . ', 3)"><i class="bi bi-x-circle-fill" title="Transport Settled Not Completed"></i></button>';
                                                                            }
                                                                        }
                                                                        ?>

                                                                    </div>
                                                                </td>

                                                                <td></td>
                                                                <td></td>
                                                                <td>

                                                                    <div class="d-flex align-items-center">
                                                                        <?php
                                                                        if (isset($row->Finalize) && $row->Finalize == 1) {
                                                                            ?>
                                                                            <a href="<?= base_url('tour/tour_details/show_details?id=' . $row->tour_id . '&start=' . $row->start_date . '&end=' . $row->end_date); ?>"
                                                                               class="btn btn-warning btn-sm me-2"
                                                                               target="_blank" data-toggle="tooltip"
                                                                               title="View"><i
                                                                                        class="bi bi-eye-fill"></i></a>
                                                                            <a href="<?= base_url('tour/quotation/timeline/' . $row->tour_id . '?edit=2'); ?>"
                                                                               class="btn btn-secondary btn-sm"
                                                                               target="_blank" data-toggle="tooltip"
                                                                               title="Edit"><i class="bi bi-pencil"></i></a>
                                                                        <?php } else { ?>
                                                                            <button type="button"
                                                                                    class="btn btn-danger btn-sm delete-btn"
                                                                                    title="Delete"
                                                                                    data-tour="<?= $row->tour_id; ?>"><i
                                                                                        class="bi bi-trash"></i>
                                                                            </button>
                                                                        <?php } ?>
                                                                        <a href="<?= base_url('chat/open/' . $row->tour_id . '/' . $row->agent); ?>"
                                                                           class="btn btn-dark btn-sm ms-1"
                                                                           target="_blank" data-toggle="tooltip"
                                                                           title="Open Chat for this Tour">
                                                                            <i class="bi bi-chat-dots-fill"></i>
                                                                        </a>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                            <?php
                                                        }
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
        </div>
    </div>
    <!-- end main content-->

    <!-- Manual ID Modal -->
    <div class="modal fade" id="manualIdModal" tabindex="-1" aria-labelledby="manualIdModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="manualIdModalLabel">Enter Manual ID</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <form action="" id="form_submit" accept-charset="utf-8">
                            <input type="hidden" name="order_id" id="order_id">
                            <input type="hidden" name="status" id="status" value="0">

                            <div class="mb-4">
                                <div class="mb-3">
                                    <label class="form-label">Manual ID<font
                                                color="#FF0000"><strong>*</strong></font></label>
                                    <input type="text" name="manual_id" id="manual_id" class="form-control txt_nav"
                                           placeholder="" required/>
                                </div>


                                <button type="submit" class="btn btn-success mt-2" id="add_amount">Save</button>
                            </div>
                        </form>

                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="agentrefModal" tabindex="-1" aria-labelledby="agentrefModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="agentrefModalLabel">Enter Agent Reference</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <form action="" id="form_submit_agent_ref" accept-charset="utf-8">
                            <input type="hidden" name="order_id" id="order_id">
                            <input type="hidden" name="status" id="status" value="0">

                            <div class="mb-4">
                                <div class="mb-3">
                                    <label class="form-label">Agent Reference<font
                                                color="#FF0000"><strong>*</strong></font></label>
                                    <input type="text" name="agent_ref" id="agent_ref" class="form-control txt_nav"
                                           placeholder="" required/>
                                </div>


                                <button type="submit" class="btn btn-success mt-2">Save</button>
                            </div>
                        </form>

                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <!-- End Manual ID Modal -->

    <!-- Passenger Modal -->
    <div class="modal fade" id="AddmemberModal" tabindex="-1" aria-labelledby="AddmemberModalLabel" aria-hidden="true"
         data-bs-backdrop="static">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="AddmemberModalLabel">Add Pax Details</h1>
                    <!-- <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button> -->
                    <input type="hidden" name="modal_tour_id" id="modal_tour_id">
                    <input type="hidden" name="pax_cc" id="pax_cc">
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="mb-4">
                            <label class="form-label" for="passenger">Passenger Name<font
                                        color="#FF0000"><strong>*</strong></font></label>
                            <input class="form-control txt_nav" type="text" id="passenger" name="passenger"
                                   placeholder="" required/>
                            <button type="button" class="btn btn-success mt-2" id="add_passenger">Save</button>
                        </div>
                        <div class="px-3">
                            <table class="table table-bordered table-hover">
                                <thead>
                                <tr>
                                    <th scope="col">#</th>
                                    <th scope="col">Passenger Name</th>
                                    <!-- <th scope="col">Action</th> -->
                                </tr>
                                </thead>
                                <tbody id="passenger_details">

                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-info" data-bs-dismiss="modal" id="btn-ok">Ok</button>
                    <!-- <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button> -->
                </div>
            </div>
        </div>
    </div>
</div>
<!-- END layout-wrapper -->

<!-- Modal HTML (hidden by default) -->
<div class="modal fade" id="hotelBookingEmailModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
     aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">

        <style>
            /*@media print {*/
            @page {
                size: A4;
                margin: 15mm;
            }

            body {
                font-family: 'Times New Roman', serif;
                color: #333;
                margin: 0;
                padding: 0;
                /* font-size: medium; */
                /* font-weight: 400; */
            }

            .no-print {
                display: none !important;
            }

            .main-content {
                width: 100%;
            }

            .container-fluid {
                padding: 20px;
            }

            .invoice-header {
                text-align: center;
                border-bottom: 1px solid #000;
                margin-bottom: 20px;
                padding-bottom: 10px;
                margin-right: 50px;
            }

            .invoice-header h3 {
                margin: 0;
                font-size: 22px;
                text-transform: uppercase;
                font-weight: bold;
            }

            .invoice-header p {
                font-size: 14px;
                margin: 5px 0;
            }

            .invoice-logo img {
                height: 60px;
                float: left;
            }

            .invoice-details {
                width: 100%;
                margin-bottom: 20px;
                border-collapse: collapse;
            }

            .invoice-details th,
            .invoice-details td {
                text-align: left;
                padding: 10px;
                border: 1px solid #ddd;
            }

            .invoice-details th {
                background-color: #f2f2f2;
            }

            .invoice-main-details th,
            .invoice-main-details td {
                background-color: #f2f2f2;
                text-align: center;
                border: 1px solid #000;
            }

            .invoice-footer {
                margin-top: 30px;
                font-size: 14px;
                text-align: center;
                border-top: 1px solid #000;
                padding-top: 10px;
            }

            .details-header {
                font-weight: bold;
                margin-bottom: 5px;
                text-transform: uppercase;
                font-size: larger;
            }

            .tour-details-header {
                font-weight: bold;
                margin-bottom: 5px;
                /* text-transform: uppercase; */
                /* font-size: larger; */
            }

            .amount-cell {
                text-align: right;
            }

            .total-row th,
            .total-row td {
                font-weight: bold;
                font-size: 16px;
                margin: 0px;
            }

            /*}*/

            /* Add some styles for non-print preview */
            body {
                font-family: Arial, sans-serif;
                color: #333;
            }

            .card {
                border: 1px solid #ddd;
                padding: 20px;
                margin-bottom: 20px;
                background-color: #f9f9f9;
            }
        </style>

        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Invoice Details & Email Form</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body" id="invoiceModalContent">
                <!-- Form Section -->
                <form action="javascript:void(0);" class="hotel_booking_email_form" id="hotel_booking_email_form">
                    <div class="row">
                        <div class="mb-4">
                            <input type="hidden" name="tour_id" id="e_tour_id">
                            <input type="hidden" name="dates" id="e_dates">
                            <input type="hidden" name="id" id="e_id">
                            <div class="mb-3">
                                <label class="form-label" for="em_to">To</label>
                                <select name="em_to" id="em_to" class="form-control">
                                    <?php foreach ($customers as $customer): ?>
                                        <option value="<?= $customer['Sup_Acc_No']; ?>" data-id="<?= $customer['Sup_Acc_No']; ?>">
                                            <?= $customer['Name']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="em_cc">CC</label>
                                <select name="em_cc[]" id="em_cc" class="form-control em_cc"></select>
                                <small>
                                    All emails are CC to <i>accounts@avenirholidays.com</i>,
                                    <i>nf@avenirholidays.com</i> by default.
                                </small>
                            </div>

                        </div>
                    </div>
                </form>

                <!-- Invoice Content (Dynamically Added) -->
                <div id="invoiceSection" class="invoice-section">
                    <!-- The invoice content will be dynamically inserted here -->
                </div>
            </div>

            <!-- Modal Footer with Print Button -->
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>

                <button class="btn btn-primary invoice_print me-2"
                        id="mail_tour_id"
                        title="Invoice Reprint"
                        data-tour=""
                        data-customer_id=""
                >
                    Send Receipt
                </button>
            </div>
        </div>
    </div>
</div>


<?= view('layouts/inpage_footer') ?>
<?= view('layouts/theme_settings') ?>
<?= view('layouts/scripts') ?>

<script>

    $('.em_cc').select2({
        placeholder: " Select",
        allowClear: true,
        dropdownParent: $("#hotelBookingEmailModal"),
        multiple: true,
        tags: true
    });

    //change data-customer_id when onchange on em_to
    $('#em_to').on('change', function () {
        $('#mail_tour_id').attr('data-customer_id', $(this).val());
    });

    document.getElementById('mail_tour_id').addEventListener('click', function () {
        var tourId = this.getAttribute('data-tour');
        var customerId = this.getAttribute('data-customer_id');
        var ccEmails = $('#em_cc').val(); // Get the selected CC emails

        // Example: Open a new window with the tour, consignee, and CC details
        window.open("<?php echo base_url(); ?>accounts/debtor_invoice_new/print?tour=" + tourId + "&customer_id=" + customerId + "&cc=" + ccEmails.join(','), '_blank');
    });

    $(".tour_confirm").on('click', function (e) {
        e.preventDefault();

        var tour_id = $(this).data('tour');

        window.location.href = "<?php echo base_url(); ?>accounts/debtor_invoice_new?tour_id=" + tour_id;

    });

    $(".tour_additional_inv").on('click', function (e) {
        e.preventDefault();

        var tour_id = $(this).data('tour');

        window.location.href = "<?php echo base_url(); ?>accounts/debtor_invoice_new/additional_inv?tour_id=" + tour_id;

    });

    $(".invoice_print").on('click', function (e) {
        e.preventDefault();

        var tour_id = $(this).data('tour');
        $.ajax({
            url: '<?php echo base_url('tour/tour_dashboard/search_debtor_invoice') ?>',
            type: 'POST',
            data: {
                tour_id: tour_id,
            },
            dataType: 'json',
            success: function (data) {

                if (data.status == 200) {

                    window.open("<?php echo base_url(); ?>accounts/debtor_invoice_new/print?tour=" + tour_id, '_blank');

                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message ? data.message : 'An error occured while processing the request',
                    });
                }
            },
            error: function () {

                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occured while processing the request',
                });
            }
        });
    });

    // .open_em_modal click
    $(document).on('click', '.open_em_modal', function () {
        // Sample invoice data (you can replace this with dynamic data)
        // const invoiceDetails = {
        //     invoice_no: "INV12345",
        //     date: "2024-12-17",
        //     manual_id: "TID456",
        //     consignee: "John Doe",
        //     hotel_des: "Luxury Hotel Stay",
        //     activity_des: "Sightseeing Tour",
        //     dollar_amount: "1000"
        // };

        //fetch data from base_url/accounts/debtor_invoice_new/sent_mail
        var tour_id = $(this).data('tour');

        //set mail_tour_id
        $('#mail_tour_id').attr('data-tour', tour_id);


        $.ajax({
            url: '<?php echo base_url('accounts/debtor_invoice_new/sent_mail') ?>',
            type: 'GET',
            data: {
                tour_id: tour_id,
            },
            dataType: 'json',
            success: function (data) {

                if (data) {
                    let invoiceDetails = data.invoice_details; // Use snake_case
                    let consigneeDetails = data.consignee_details; // For customer details


                    // Check if the ID exists in the options
                    let customerIdToSelect = invoiceDetails.agent; // Adjust the property name
                    if ($('#em_to option[value="' + customerIdToSelect + '"]').length > 0) {
                        $('#em_to').val(customerIdToSelect); // Set the dropdown value

                        $('#mail_tour_id').attr('data-customer_id', customerIdToSelect);

                    } else {
                        console.error('Customer ID not found in dropdown');
                    }



                    // Inject the dynamically generated content into the modal
                    const modalContent = `
            <div class="page-content">
                <div class="container-fluid">

                    <!-- Invoice Header -->
                    <div class="invoice-header">
                        <div class="invoice-logo">
                            <img src="<?= base_url() ?>assets/images/Logo-new.png" alt="Logo">
                        </div>
                        <h3>AVENIR HOLIDAYS (PVT) LTD</h3>
                        <p>NO. 126/1, Park Road, Colombo -5, SRI LANKA</p>
                        <p>Email: info@avenirholidays.com</p>
                    </div>

                    <!-- Invoice Details -->
                    <div class="card">
                        <div class="details-header">Invoice</div>
                        <table class="invoice-details">
                            <tr>
                                <th>Invoice No</th>
                                <td>${invoiceDetails.invoice_no}</td>
                                <th>Date</th>
                                <td>${invoiceDetails.date}</td>
                                <th>Invoiced Customer</th>
                            </tr>
                            <tr>
                                <th colspan="4">
                                    Tour Id - ${invoiceDetails.manual_id}<br>
                                    Sri Lanka Tour Arrangement for ${invoiceDetails.consignee}
                                </th>
                                <td colspan="4">
                                    ${consigneeDetails.long_name}<br>
                                    ${consigneeDetails.Address}
                                </td>
                            </tr>
                        </table>
                        <br>

                        <!-- Tour Details -->
                        <div class="tour-details-header">Tour Details</div>
                        <table class="invoice-details">
                            <thead>
                                <tr>
                                    <th>Details</th>
                                    <th class="amount-cell">Amount (US$)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>${invoiceDetails.hotel_des}</td>
                                    <td class="amount-cell">${invoiceDetails.hotel_amount || 0}</td>
                                </tr>
                                <tr>
                                    <td>${invoiceDetails.activity_des || 'N/A'}</td>
                                    <td class="amount-cell">${invoiceDetails.activity_amount || 0}</td>
                                </tr>
                                <tr class="total-row">
                                    <th style="text-align: center;">TOTAL</th>
                                    <th class="amount-cell">USD ${parseFloat(invoiceDetails.dollar_amount).toFixed(2)}</th>
                                </tr>
                                <tr class="total-row">
                                    <th colspan="2">
                                        ${invoiceDetails.dollar_amount} only
                                    </th>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Bank Details -->
                    <div class="bank-details">
                        <div class="tour-details-header" style="text-decoration: underline; text-align: left; margin-bottom: 2px;">Bank Details</div>
                        <div style="display: flex; flex-direction: column; align-items: flex-start;">
                            <p style="margin: 0;"><span>A/C Name:</span><span style="margin-left: 35px;">Avenir Holidays (Pvt) Ltd</span></p>
                            <p style="margin: 0;"><span>A/C Type:</span><span style="margin-left: 42px;">Savings A/C</span></p>
                            <p style="margin: 0;"><span>A/C No:</span><span style="margin-left: 57px;">5000028818</span></p>
                            <p style="margin: 0;"><span>Bank:</span><span style="margin-left: 70px;">Commercial Bank</span></p>
                            <p style="margin: 0;"><span>Branch:</span><span style="margin-left: 55px;">Ward Place / Branch code - 145</span></p>
                            <p style="margin: 0;"><span>Bank Code:</span><span style="margin-left: 26px;">7056</span></p>
                            <p style="margin: 0;"><span>Swift Code:</span><span style="margin-left: 26px;">CCEYKLX</span></p>
                        </div>
                    </div>
                </div>

                <!-- Invoice Footer -->
                <div class="invoice-footer">
                    Thank you for choosing Avenir Holidays! <br>
                    For any inquiries, please contact us at: info@avenirholidays.com
                </div>
            </div>
        `;

                    // Inject into the modal or target section
                    $('#invoiceSection').html(modalContent);

                    // Show modal
                    $('#hotelBookingEmailModal').modal('show');
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message ? data.message : 'An error occurred while processing the request',
                    });
                }
            },


            error: function () {

                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occured while processing the request',
                });
            }
        });


    });

    $(".delete-btn").on('click', function (e) {
        e.preventDefault();

        var tour_id = $(this).data('tour');
        $.ajax({
            url: '<?php echo base_url('tour/tour_dashboard/delete') ?>',
            type: 'GET',
            data: {
                tour_id: tour_id,
            },
            dataType: 'json',
            success: function (data) {

                if (data.status == 200) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: data.message,
                    }).then((result) => {
                        if (result.isConfirmed) {
                            location.reload();
                        }
                    });

                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message ? data.message : 'An error occured while processing the request',
                    });
                }
            },
            error: function () {

                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occured while processing the request',
                });
            }
        });

    });

    function addManualID(index) {

        $("#order_id").val(index);
        $("#manualIdModal").show();
    }

    function addagentRefD(index) {

        $("#order_id").val(index);
        $("#agentrefModal").show();
    }

    function addPassenger(index, pax_count) {

        $("#modal_tour_id").val(index);
        $("#pax_cc").val(pax_count);
        $("#AddmemberModal").show();
        load_passenger(index);

    }

    $(".btn-close").on('click', function () {
        $("#manualIdModal").hide();
        $("#manualIdModal input[type='text']").val('');
        $("#AddmemberModal").hide();
        location.reload();
    });

    $(".agent_btn-close").on('click', function () {
        $("#agentrefModal").hide();
        $("#agentrefModal input[type='text']").val('');
        // $("#AddmemberModal").hide();
        location.reload();
    });


    $("#form_submit").on('submit', function (e) {
        e.preventDefault();

        var order_id = $("#order_id").val();
        var manual_id = $("#manual_id").val();
        var status = $("#status").val();

        if (status == 0) {
            $url = '<?php echo base_url('tour/tour_dashboard/add_manual_id') ?>';
        } else {
            $url = '<?php echo base_url('tour/tour_dashboard/edit_manual_id') ?>';
        }

        $.ajax({
            url: $url,
            type: 'POST',
            data: {
                order_id: order_id,
                manual_id: manual_id,
            },
            dataType: 'json',
            success: function (data) {

                if (data.status == 200) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: data.message,
                    }).then((result) => {
                        if (result.isConfirmed) {
                            location.reload();
                        }
                    });

                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message ? data.message : 'An error occured while processing the request',
                    });
                }
            },
            error: function () {

                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occured while processing the request',
                });
            }
        });
    });


    $("#form_submit_agent_ref").on('submit', function (e) {
        e.preventDefault();

        var order_id = $("#order_id").val();
        var agent = $("#agent_ref").val();
        var status = $("#status").val();

        if (status == 0) {
            $url = '<?php echo base_url('tour/tour_dashboard/add_agent_ref') ?>';
        } else {
            $url = '<?php echo base_url('tour/tour_dashboard/edit_agent_ref') ?>';
        }

        $.ajax({
            url: $url,
            type: 'POST',
            data: {
                order_id: order_id,
                agent: agent,
            },
            dataType: 'json',
            success: function (data) {

                if (data.status == 200) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: data.message,
                    }).then((result) => {
                        if (result.isConfirmed) {
                            location.reload();
                        }
                    });

                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message ? data.message : 'An error occured while processing the request',
                    });
                }
            },
            error: function () {

                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occured while processing the request',
                });
            }
        });
    });

    // load manual id edit model
    $(".manual_id_edit").on('click', function () {
        var tour_id = $(this).data('tour');
        var edit = $(this).data('edit');
        $.ajax({
            url: '<?php echo base_url('tour/tour_dashboard/get_manual_id') ?>',
            type: 'POST',
            data: {
                tour_id: tour_id,
            },
            dataType: 'json',
            success: function (data) {

                if (data.status == 200) {
                    $("#order_id").val(tour_id);
                    $("#manual_id").val(data.manual_id);
                    $("#status").val(edit);
                    $("#manualIdModal").show();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message ? data.message : 'An error occured while processing the request',
                    });
                }
            },
            error: function () {

                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occured while processing the request',
                });
            }
        });
    });

    // load manual id edit model
    $(".agent_ref_edit").on('click', function () {
        var tour_id = $(this).data('tour');
        var edit = $(this).data('edit');
        $.ajax({
            url: '<?php echo base_url('tour/tour_dashboard/get_agent_ref') ?>',
            type: 'POST',
            data: {
                tour_id: tour_id,
            },
            dataType: 'json',
            success: function (data) {

                if (data.status == 200) {
                    $("#order_id").val(tour_id);
                    $("#agent_ref").val(data.agent_ref);
                    $("#status").val(edit);
                    $("#manualIdModal").show();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message ? data.message : 'An error occured while processing the request',
                    });
                }
            },
            error: function () {

                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occured while processing the request',
                });
            }
        });
    });

    /*******************Hoterl Reservation Functions************************/

    function hotelReserve(tour_id, start_date, end_date, type) {

        if (end_date >= start_date) {
            window.open("<?php echo base_url(); ?>tour/booking_confirm/show_details?id=" + tour_id + "&start=" + start_date + "&end=" + end_date + "&type=" + type, '_blank');
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                html: 'The end date must be greater than the start date. Please adjust the dates to proceed with the tour.<br><br><b>Start Date: </b>' + start_date + '<br><b>End Date: </b>' + end_date,
            });

        }


    }

    /************************Tranport payment***********************************/
    function transportPayment(tour_id, start_date, end_date, consignee, pax_count, type) {

        if (end_date >= start_date) {
            window.open("<?php echo base_url(); ?>tour/booking_confirm/show_transport?id=" + tour_id + "&start=" + start_date + "&end=" + end_date + "&consignee=" + consignee + "&pax_count=" + pax_count + "&type=" + type, '_blank');
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                html: 'The end date must be greater than the start date. Please adjust the dates to proceed with the tour.<br><br><b>Start Date: </b>' + start_date + '<br><b>End Date: </b>' + end_date,
            });

        }
    }

    /*****************passengers***********************************/
    function load_passenger(modal_tour_id) {

        $.ajax({
            type: "GET",
            url: "<?php echo base_url(); ?>tour/tour/pax_all/" + modal_tour_id,
            dataType: "json",
            success: function (response) {

                $('#passenger_details').empty();
                $.each(response, function (index, value) {
                    $('#passenger_details').append(
                        '<tr>' +
                        '<th scope="row">' + (index + 1) + '</th>' +
                        '<td>' + value.pass_name + '</td>' +
                        '<td style="width: 25%;">' +
                        '<button type="button" class="btn btn-sm btn-danger" onClick="delete_pax(' + value.id + ')"><i class="bi bi-trash"></i></button>' +

                        '</td>' +
                        '</tr>'
                    );

                });
            },
            error: function (jqXHR, textStatus, errorThrown) {
                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    text: 'Something went wrong!',
                });
            }
        });

    }

    function delete_pax(id) {

        var pax_id = id;
        var modal_tour_id = $('#modal_tour_id').val();

        Swal.fire({
            title: 'Are you sure?',
            text: "You want to delete this Passenger!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    type: "get",
                    url: "<?php echo base_url(); ?>tour/tour/pax_delete",
                    data: {
                        id: id
                    },
                    dataType: "json",
                    success: function (response) {
                        if (response.status == 200) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: response.message,
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    load_passenger(modal_tour_id);

                                }
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Oops...',
                                text: response.message,
                            });
                        }
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: 'Something went wrong!',
                        });
                    }
                });
            }
        });
    }

    $('#passenger').keydown(function (e) {
        if (e.keyCode == 13) {
            $('#add_passenger').click();
        }
    });

    // add_passenger button
    $('#add_passenger').on('click', function () {
        var passenger = $('#passenger').val();
        var modal_tour_id = $('#modal_tour_id').val();
        var pax_coun = $('#pax_cc').val();

        if (passenger == '') {
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: 'Name is required!',
            });
        } else {
            $.ajax({
                type: "POST",
                url: "<?php echo base_url(); ?>tour/tour/pax_create",
                data: {
                    passenger: passenger,
                    modal_tour_id: modal_tour_id,
                    pax_coun: pax_coun
                },
                dataType: "json",
                success: function (response) {
                    if (response.status == 201) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: response.message,
                        }).then((result) => {
                            if (result.isConfirmed) {
                                load_passenger(modal_tour_id);
                                $('#passenger').val('');
                            }
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: response.message,
                        });
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Something went wrong!',
                    });
                }
            });
        }
    });

    $('#btn-ok').on('click', function () {
        location.reload();
    });

    function get_dates_between(from, to) {
        var dates = [];
        var currentDate = new Date(from);
        var endDate = new Date(to);
        while (currentDate <= endDate) {
            dates.push(new Date(currentDate));
            currentDate.setDate(currentDate.getDate() + 1);
        }
        return dates;
    }

    function get_passengers() {
        try {
            var passengers = '<?php /*echo json_encode($passengers) */?>';
            passengers = JSON.parse(passengers);
            var passengerNames = '';
            if (passengers?.length > 0) {
                passengers.forEach(function (passenger) {
                    passengerNames += passenger.pass_name + ' , ';
                });
            }
            if (passengerNames.endsWith(', ')) {
                passengerNames = passengerNames.slice(0, -2);
            }
            return passengerNames;
        } catch (e) {
            console.log(e);
            return [];
        }
    }


</script>

</body>

</html>