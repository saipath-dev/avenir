<?= view('layouts/head') ?>

<body>

    <!-- Begin page -->
    <div id="layout-wrapper">

        <?= view('layouts/header') ?>
        <?= view('layouts/sidebar') ?>

        <?php
        $advance = $expenses = $setteld = '';
        $advance_body = $expenses_body = $setteld_body = '';
        if ($type == 1) {
            $advance = 'active';
            $advance_body = 'show active';
        } else if ($type == 2) {
            $expenses = 'active';
            $expenses_body = 'show active';
        } else {
            $setteld = 'active';
            $setteld_body = 'show active';
        }
        ?>
        <script>
            $(document).ready(function() {
                var tourCondition = <?php echo json_encode($tour_condition); ?>;


                var urlParams = new URLSearchParams(window.location.search);
                var startParam = urlParams.get('start');

                var start = new Date(startParam);

                var year = start.getFullYear();
                var month = start.getMonth() + 1;

                var message = '';

                if (tourCondition[0]['hotel_only'] == 1) {
                    message = 'Please note that this tour only includes hotel facilities.';
                }

                if ((tourCondition[0]['hotel_only'] == 1)) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: message,
                    });

                    setTimeout(() => {
                        window.location.href = '<?php echo base_url(); ?>tour/tour_dashboard/monthly_dashboard?year=' + year + '&month=' + month;
                    }, 2000);
                }
            });
        </script>

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
                                        <h4 class="p-title">Transport Details</h4>
                                    </div>
                                </div>

                                <div class="row clearfix mt-3">
                                    <div class="col-md-12">
                                        <div class="col-md-12">
                                            <div class="card p-3">
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
                                                                    <input type="hidden" name="tour_id" id="tour_id" value="<?php echo $detail['manual_id'] ?>">
                                                                    <h6 class="fw-bold" id="t_id"><?php echo $detail['manual_id'] ?></h6>
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

                                <div class="row mb-4">
                                    <div class="col-xxl-12">
                                        <!-- <h5 class="mb-3">Custom Hover Tabs</h5> -->
                                        <div class="card">
                                            <!-- <div class="card-body">
                                                <p class="text-muted"></p>
                                            </div> -->
                                            <div class="border">
                                                <ul class="nav nav-pills custom-hover-nav-tabs">
                                                    <li class="nav-item">
                                                        <a href="#custom-hover-customere" data-bs-toggle="tab" aria-expanded="false" class="nav-link <?php echo $advance ?>">
                                                            <i class="bi bi-wallet nav-icon nav-tab-position"></i>
                                                            <h5 class="nav-titl nav-tab-position m-0">Advance Payment</h5>
                                                        </a>
                                                    </li>
                                                    <li class="nav-item">
                                                        <a href="#custom-hover-description" data-bs-toggle="tab" aria-expanded="true" class="nav-link <?php echo $expenses ?>">
                                                            <i class="bi bi-cash-coin nav-icon nav-tab-position"></i>
                                                            <h5 class="nav-titl nav-tab-position m-0">Expenses</h5>
                                                        </a>
                                                    </li>
                                                    <li class="nav-item">
                                                        <a href="#custom-hover-reviews" data-bs-toggle="tab" aria-expanded="false" class="nav-link <?php echo $setteld ?>">
                                                            <i class="bi bi-wallet2 nav-icon nav-tab-position"></i>
                                                            <h5 class="nav-titl nav-tab-position m-0">Balance Payment</h5>
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                            <div class="card-body">
                                                <div class="tab-content text-muted">
                                                    <div class="tab-pane <?php echo $advance_body ?>" id="custom-hover-customere">

                                                        <form action="javascript:void(0);" id="form_submit_advance" accept-charset="utf-8">
                                                            <fieldset>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Transport Agent<font color="#FF0000"><strong>*</strong></font></label>
                                                                    <select name="agent" id="agent" class="js-example-basic-single form-control" style="width: 100%;" required>
                                                                        <?php
                                                                        echo "<option></option>";
                                                                        foreach ($drivers as $agent) {
                                                                            $selected = '';
                                                                            if ($agent['driver'] == $detail['transport_agent']) {
                                                                                $selected = 'selected';
                                                                            }
                                                                            echo "<option value='" . $agent['driver'] . "' " . $selected . " >" . $agent['driver_name'] . "</option>";
                                                                        }
                                                                        ?>
                                                                    </select>

                                                                </div>

                                                                <div class="mb-3">
                                                                    <label class="form-label">Bank Account<font color="#FF0000"><strong>*</strong></font></label>

                                                                    <select name="bank_acc" id="bank_acc" class="js-example-basic-single form-control" style="width: 100%;" required>
                                                                        <?php echo "<option></option>";
                                                                        foreach ($bank_acc as $bank) {
                                                                            echo "<option value='" . $bank['L_Code'] . "'>" . $bank['PName'] . "</option>";
                                                                        }
                                                                        ?>
                                                                    </select>
                                                                </div>

                                                                <div class="mb-3">
                                                                    <label class="form-label" for="ref">Reference No<font color="#FF0000"><strong>*</strong></font></label>
                                                                    <input class="form-control txt_nav" type="text" id="ref" name="ref" placeholder="" required />
                                                                </div>

                                                                <div class="mb-3">
                                                                    <label class="form-label" for="payment">Advance Payment<font color="#FF0000"><strong>*</strong></font></label>
                                                                    <input class="form-control txt_nav" type="text" id="payment" name="payment" placeholder="" required />
                                                                </div>

                                                                <div class="mb-3">
                                                                    <label class="form-label" for="advance_date">Advance Payment Date<font color="#FF0000"><strong>*</strong></font></label>
                                                                    <input class="form-control" type="text" id="advance_date" name="advance_date" placeholder="" data-provide="datepicker" data-date-format="yyyy-mm-dd" required />
                                                                </div>

                                                                <br>
                                                                <button type="submit" class="btn btn-success w-100px me-5px" id="add_transport">Save</button>
                                                                <br>
                                                                <br>
                                                            </fieldset>
                                                        </form>

                                                        <div class="table-responsive">
                                                            <table class="table mb-0 d-none history_table" style="color: #212529;" id="history_table">
                                                                <h6 class="table_title d-none">Payment History (Accepted Vouchers)</h6>
                                                                <thead>
                                                                    <tr>
                                                                        <th scope="col">#</th>
                                                                        <th scope="col">Date</th>
                                                                        <th scope="col">Pay</th>
                                                                        <th scope="col">Voucher No</th>
                                                                        <th scope="col">Discription</th>
                                                                        <th scope="col">Amount</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody id="payment_history">

                                                                </tbody>
                                                            </table>
                                                        </div>

                                                        <br>
                                                        <div class="table-responsive">
                                                            <table class="table mb-0 d-none pending_table" style="color: #212529;" id="pending_table">
                                                                <h6 class="table_text_voucher d-none">Vouchers Status (Pending, Reject, Cancel)</h6>
                                                                <thead>
                                                                    <tr>
                                                                        <th scope="col">#</th>
                                                                        <th scope="col">Pay</th>
                                                                        <th scope="col">Bank</th>
                                                                        <th scope="col">Voucher Number</th>
                                                                        <th scope="col">Amount</th>
                                                                        <th scope="col">Status</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody id="pending_vouchers">

                                                                </tbody>
                                                            </table>
                                                        </div>

                                                    </div>
                                                    <div class="tab-pane <?php echo $expenses_body ?>" id="custom-hover-description">
                                                        <form action="javascript:void(0);" id="form_submit" accept-charset="utf-8">
                                                            <h6 class="mt-2">Expenses Details</h6>
                                                            <fieldset>
                                                                <div class="row">
                                                                    <div class="mb-3 col-md-6">
                                                                        <label class="form-label" for="tagent">Select Agent<font color="#FF0000"><strong>*</strong></font></label>

                                                                        <select name="tagent" id="tagent" class="js-example-basic-single form-control" style="width: 100%;" required>
                                                                            <!-- <option value="">Select a Agent...</option> -->
                                                                            <?php
                                                                            // echo '<option value="">Select a Agent...</option>';
                                                                            // foreach ($agents as $ids) {
                                                                            //     $selected = '';
                                                                            //     if (!empty($detail['transport_agent'])) {
                                                                            //         if ($ids['Acc_No'] == $detail['transport_agent']) {
                                                                            //             $selected = 'selected';
                                                                            //         }
                                                                            //     }
                                                                            //     echo '<option value="' . $ids['Acc_No'] . '" ' . $selected . ' >' . $ids['Pay'] . '</option>';
                                                                            // }
                                                                            // foreach ($transport_agent as $agent) {
                                                                            //     // if $agent['Sup_Acc_No'] in $agents, skip 
                                                                            //     $found = false;
                                                                            //     foreach ($agents as $ids) {
                                                                            //         if ($ids['Acc_No'] == $agent['Sup_Acc_No']) {
                                                                            //             $found = true;
                                                                            //             break;
                                                                            //         }
                                                                            //     }
                                                                            //     if ($found) {
                                                                            //         continue;
                                                                            //     }
                                                                            //     echo "<option value='" . $agent['Sup_Acc_No'] . "'>" . $agent['Name'] . "</option>";
                                                                            // }

                                                                            echo "<option></option>";
                                                                            foreach ($drivers as $agent) {
                                                                                $selected = '';
                                                                                if ($agent['driver'] == $detail['transport_agent']) {
                                                                                    $selected = 'selected';
                                                                                }
                                                                                echo "<option value='" . $agent['driver'] . "' " . $selected . " >" . $agent['driver_name'] . "</option>";
                                                                            }
                                                                            ?>
                                                                        </select>
                                                                    </div>
                                                                </div>

                                                                <div class="row">
                                                                    <div class="mb-3 col-md-6">
                                                                        <label class="form-label" for="category">Expenses Type<font color="#FF0000"><strong>*</strong></font></label>
                                                                        <select name="category" id="category" class="js-example-basic-single form-control" style="width: 100%;" required>
                                                                            <option value="">Select a Category...</option>
                                                                            <!-- <option value="4-8-106-10386">Hotel</option>
                                                                            <option value="4-8-106-10385">Transport</option> -->
                                                                            <option value="6-9-23-10640">Entrance Fee</option>
                                                                            <option value="6-9-23-10641">Other</option>
                                                                        </select>
                                                                    </div>
                                                                    <div class="mb-3 col-md-6">
                                                                        <label class="form-label" for="supplier">Expenses Acc<font color="#FF0000"><strong>*</strong></font></label>
                                                                        <select name="supplier" id="supplier" class="js-example-basic-single form-control" style="width: 100%;" required>
                                                                            <option value="">Select a Supplier...</option>

                                                                        </select>
                                                                    </div>
                                                                </div>
                                                                <div class="row">

                                                                    <div class="mb-3 col-md-6">
                                                                        <label class="form-label" for="inv">Invoice No <font color="#FF0000"><strong>*</strong></font></label>
                                                                        <input class="form-control" type="text" id="inv" name="inv" placeholder="" required />
                                                                    </div>
                                                                    <div class="mb-3 col-md-6">
                                                                        <label class="form-label" for="date">Date <font color="#FF0000"><strong>*</strong></font></label>
                                                                        <input class="form-control" type="text" id="date" name="date" placeholder="" data-provide="datepicker" data-date-format="yyyy-mm-dd" required />
                                                                    </div>
                                                                </div>
                                                                <div class="row">
                                                                    <div class="mb-3 col-md-4">
                                                                        <label class="form-label" for="dollerrate" id="ddlabel">Doller Rate <font color="#FF0000"><strong>*</strong></font></label>
                                                                        <input class="form-control" type="text" id="dollerrate" name="dollerrate" placeholder="" required />
                                                                    </div>
                                                                    <div class="mb-3 col-md-4">
                                                                        <label class="form-label" for="amount" id="ddalabel">$ Amount <font color="#FF0000"><strong>*</strong></font></label>
                                                                        <input class="form-control" type="text" id="amount" name="amount" placeholder="" required />
                                                                    </div>

                                                                    <div class="mb-3 col-md-4">
                                                                        <label class="form-label" for="rsamount">LKR Amount <font color="#FF0000"><strong>*</strong></font></label>
                                                                        <input class="form-control" type="text" id="rsamount" name="rsamount" placeholder="" required readonly />
                                                                    </div>
                                                                </div>
                                                                <br>
                                                                <button type="submit" class="btn btn-success w-100px me-5px" id="save_btn">Save</button>
                                                            </fieldset>
                                                        </form>
                                                        <div class="col-md-12 d-none" id="added_creditor_invoice">
                                                            <div class="card p-4">
                                                                <div class="body">
                                                                    <div class="table-responsive">
                                                                        <table class="table js-basic-example table-hover dataTable table-custom align-middle">
                                                                            <thead class="thead-dark">
                                                                                <tr>
                                                                                    <th>Tour</th>
                                                                                    <th>Tour Name</th>
                                                                                    <th>Tour Agent</th>
                                                                                    <th>Transport Agent</th>
                                                                                    <th>Expenses Type</th>
                                                                                    <th>Supplier</th>
                                                                                    <th>Invoice No</th>
                                                                                    <th>Date</th>
                                                                                    <th>LKR Amount</th>
                                                                                    <th>Remove</th>
                                                                                </tr>
                                                                            </thead>
                                                                            <tbody id="creditor_invoice_tbl">

                                                                            </tbody>
                                                                        </table>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="tab-pane <?php echo $setteld_body ?>" id="custom-hover-reviews">

                                                        <div class="row">
                                                            <div class="col-md-12">
                                                                <div class="table-responsive">
                                                                    <table class="table mb-0" style="color: #212529;">
                                                                        <thead>
                                                                            <tr>
                                                                                <th scope="col">Total Trasport Amount</th>
                                                                                <th scope="col">Advance Payment</th>
                                                                                <th scope="col">Advance Total Expenses</th>
                                                                                <th scope="col">Advance Remaining Balance</th>
                                                                                <th scope="col">Total Payment</th>
                                                                                <th scope="col">Paybal Amount</th>
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody id="balance_payment">
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="card p-4">
                                                                <!-- <div class="body"> -->
                                                                <div class="row">
                                                                    <div class="col-md-6">
                                                                        <form action="javascript:void(0);" id="form_submit_balance" accept-charset="utf-8">
                                                                            <div class="mb-4">
                                                                                <label class="form-label" for="tagent">Transport Agent<font color="#FF0000"><strong>*</strong></font></label>
                                                                                <select name="transagent" id="transagent" class="js-example-basic-single form-control" style="width: 100%;" required>
                                                                                    <!-- <option value="">Select a Agent...</option> -->
                                                                                    <?php
                                                                                    // foreach ($agents as $id) {
                                                                                    //     echo '<option value="' . $id['Acc_No'] . '" >' . $id['Pay'] . '</option>';
                                                                                    // }
                                                                                    ?>
                                                                                    <?php
                                                                                    // echo '<option value="">Select a Agent...</option>';
                                                                                    // foreach ($agents as $ids) {
                                                                                    //     $selected = '';
                                                                                    //     if (!empty($detail['transport_agent'])) {
                                                                                    //         if ($ids['Acc_No'] == $detail['transport_agent']) {
                                                                                    //             $selected = 'selected';
                                                                                    //         }
                                                                                    //     }
                                                                                    //     echo '<option value="' . $ids['Acc_No'] . '" ' . $selected . ' >' . $ids['Pay'] . '</option>';
                                                                                    // }
                                                                                    // foreach ($transport_agent as $agent) {
                                                                                    //     // if $agent['Sup_Acc_No'] in $agents, skip 
                                                                                    //     $found = false;
                                                                                    //     foreach ($agents as $ids) {
                                                                                    //         if ($ids['Acc_No'] == $agent['Sup_Acc_No']) {
                                                                                    //             $found = true;
                                                                                    //             break;
                                                                                    //         }
                                                                                    //     }
                                                                                    //     if ($found) {
                                                                                    //         continue;
                                                                                    //     }
                                                                                    //     echo "<option value='" . $agent['Sup_Acc_No'] . "'>" . $agent['Name'] . "</option>";
                                                                                    // }

                                                                                    echo "<option></option>";
                                                                                    foreach ($drivers as $agent) {
                                                                                        $selected = '';
                                                                                        if ($agent['driver'] == $detail['transport_agent']) {
                                                                                            $selected = 'selected';
                                                                                        }
                                                                                        echo "<option value='" . $agent['driver'] . "' " . $selected . " >" . $agent['driver_name'] . "</option>";
                                                                                    }

                                                                                    ?>
                                                                                </select>
                                                                                <input type="hidden" id="transagent_over_paid_input">
                                                                                <div class="alert alert-primary py-2 mt-2" >
                                                                                    Current Over Payment : <strong id="transagent_over_paid"></strong>
                                                                                </div>
                                                                            </div>
                                                                            <div class="mb-4">
                                                                                <div class="mb-3">
                                                                                    <label class="form-label">Payment Method<font color="#FF0000"><strong>*</strong></font></label>
                                                                                    <select name="bank_account" id="bank_account" class="js-example-basic-single form-control" style="width: 100%;" required>
                                                                                        <?php echo "<option></option>";
                                                                                        foreach ($bank_acc as $bank) {
                                                                                            echo "<option value='" . $bank['L_Code'] . "'>" . $bank['PName'] . "</option>";
                                                                                        }
                                                                                        ?>
                                                                                    </select>
                                                                                </div>
                                                                                <div class="mb-3">
                                                                                    <label class="form-label" for="tot_trans_cost">Total Transport Amount<font color="#FF0000"><strong>*</strong></font></label>
                                                                                    <input class="form-control txt_nav" type="text" id="tot_trans_cost" name="tot_trans_cost" placeholder="" required readonly />
                                                                                </div>
                                                                                <input type="hidden" id="remaining_balance" name="remaining_balance">
                                                                                <div class="mb-3">
                                                                                    <label class="form-label" for="from_overpaid_amount">Deduct from Over Paid Amount<font color="#FF0000"><strong>*</strong></font></label>
                                                                                    <input class="form-control txt_nav" type="text" id="from_overpaid_amount" name="from_overpaid_amount" placeholder="" required />
                                                                                </div>
                                                                                <div class="mb-3">
                                                                                    <label class="form-label" for="cash">Paybal Amount<font color="#FF0000"><strong>*</strong></font></label>
                                                                                    <input class="form-control txt_nav" type="text" id="cash" name="cash" placeholder="" required readonly/>
                                                                                </div>
                                                                                <div class="mb-3">
                                                                                    <label class="form-label" for="pay_date">Payment Date<font color="#FF0000"><strong>*</strong></font></label>
                                                                                    <input class="form-control" type="text" id="pay_date" name="pay_date" placeholder="" data-provide="datepicker" data-date-format="yyyy-mm-dd" value="<?php echo date('Y-m-d'); ?>" required />
                                                                                </div>

                                                                                <button type="submit" class="btn btn-success mt-2" id="add_amount">Save</button>
                                                                            </div>
                                                                        </form>
                                                                    </div>
                                                                    <div class="col-md-6" style="border: 1px solid #000; padding-top: 15px;">
                                                                        <form action="javascript:void(0);" id="edit_total_transport" accept-charset="utf-8">
                                                                            <div class="mb-4">
                                                                                <div class="row">
                                                                                    <div class="col-md-2">
                                                                                        <label class="form-label pull-right" for="total_mile">Total Mileage</label>
                                                                                    </div>
                                                                                    <div class="col-md-2">
                                                                                        <label class="form-label pull-right" for="total_emile">Estimate Mileage</label>
                                                                                    </div>
                                                                                    <div class="col-md-4" style="text-align: end;">
                                                                                        <span id="total_mile"></span>
                                                                                    </div>
                                                                                    <div class="col-md-4">
                                                                                        <button type="button" class="btn btn-primary btn-sm actual_mile" data-bs-toggle="modal" data-bs-target="#logSheettModal">
                                                                                            Log Sheet Mileage
                                                                                        </button>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                            <div class="mb-4">
                                                                                <div class="row">
                                                                                    <div class="col-md-2">
                                                                                        <label class="form-label pull-right"></label>
                                                                                    </div>
                                                                                    <div class="col-md-2">
                                                                                        <label class="form-label pull-right" for="total_acmile">Log Sheet Mileage</label>
                                                                                    </div>
                                                                                    <div class="col-md-4" style="text-align: end;">
                                                                                        <span id="total_actual_mile"></span>
                                                                                    </div>
                                                                                    <div class="col-md-4">
                                                                                        <!-- <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#logSheettModal">
                                                                                            Log Sheet Mileage
                                                                                        </button> -->
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                            <div class="mb-4">
                                                                                <div class="row">
                                                                                    <div class="col-md-4">
                                                                                        <label class="form-label pull-right" for="vehicle_rate">Vehicle Rate</label>
                                                                                    </div>
                                                                                    <div class="col-md-4" style="text-align: end;">
                                                                                        <span id="vehicle_rate"></span>
                                                                                        <hr>
                                                                                    </div>
                                                                                    <div class="col-md-4" style="text-align: end;">
                                                                                        <!-- <input type="text" name="total_mile_rate" class="form-control  total_mile_rate txt_nav" id="total_mile_rate" placeholder="" value="70592.00"/> -->
                                                                                        <span id="total_mile_rate"></span>
                                                                                    </div>
                                                                                </div>
                                                                            </div>

                                                                            <div class="mb-4">
                                                                                <div class="row">
                                                                                    <div class="col-md-2">
                                                                                        <label class="form-label pull-right" for="tour_bata">Tour Bata</label>
                                                                                    </div>
                                                                                    <div class="col-md-2">
                                                                                        <label class="form-label pull-right" for="per_dat_rate">Per Day Rate</label>
                                                                                    </div>
                                                                                    <div class="col-md-4" style="text-align: end;">
                                                                                        <span id="per_dat_rate"></span>
                                                                                    </div>
                                                                                    <div class="col-md-4">
                                                                                        <!-- <button type="button" class="btn btn-primary btn-sm change_bata" data-bs-toggle="modal" data-bs-target="#changeBataModal">
                                                                                            Change Bata
                                                                                        </button> -->
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                            <div class="mb-4">
                                                                                <div class="row">
                                                                                    <div class="col-md-2">
                                                                                    </div>
                                                                                    <div class="col-md-2">
                                                                                        <label class="form-label pull-right" for="no_od_date">No of Dates</label>
                                                                                    </div>
                                                                                    <div class="col-md-4" style="text-align: end;">
                                                                                        <span id="no_od_date"></span>
                                                                                        <hr>
                                                                                    </div>
                                                                                    <div class="col-md-4" style="text-align: end;">
                                                                                        <!-- <input type="text" name="total_bata" class="form-control  total_bata txt_nav" id="total_bata" placeholder="" value="22500.00"/> -->
                                                                                        <span id="total_bata"></span>
                                                                                        <hr>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                            <div class="mb-4">
                                                                                <div class="row">
                                                                                    <div class="col-md-4">
                                                                                    </div>
                                                                                    <div class="col-md-4">
                                                                                    </div>
                                                                                    <div class="col-md-4">
                                                                                        <input type="text" name="total_transport_cost2" class="form-control  total_transport_cost2 txt_nav text-end" id="total_transport_cost2" placeholder="" required readonly />
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                            <!-- <button type="submit" class="btn btn-info mt-2 float-end" id="edit_amount">Edit</button> -->
                                                                        </form>
                                                                    </div>
                                                                </div>
                                                                <!-- </div> -->
                                                            </div>
                                                        </div>

                                                        <div class="row">
                                                            <div class="col-md-12">
                                                                <h6 class="mt-2">Voucher Payment History</h6>
                                                                <div class="table-responsive">
                                                                    <table class="table mb-0" style="color: #212529;">
                                                                        <thead>
                                                                            <tr>
                                                                                <th scope="col">#</th>
                                                                                <th scope="col">Date</th>
                                                                                <th scope="col">Pay</th>
                                                                                <th scope="col">Discription</th>
                                                                                <th scope="col">Status</th>
                                                                                <th scope="col">Amount</th>
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody id="setted_pay_voucher_history">
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                            </div>
                                                        </div>

                                                    </div>
                                                </div>
                                            </div><!-- end card-body -->
                                        </div>
                                    </div>
                                    <!--end col-->

                                    <!--end col-->

                                </div>

                                <!-- Modal -->
                                <div class="modal fade" id="logSheettModal" tabindex="-1" aria-labelledby="editTransportModalLabel" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="editTransportModalLabel">Add Logsheet Mileage</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <!-- Add your form fields here -->
                                                <form id="editTransportForm">
                                                    <div class="mb-3">
                                                        <label for="totalactualmileage" class="form-label">Actual Mileage<font style="color:red">* </font></label>
                                                        <input type="text" class="form-control" id="totalactualmileage" name="totalactualmileage" value="" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="actualbata" class="form-label">Actual Bata</label>
                                                        <input type="text" class="form-control" id="actualbata" name="actualbata">
                                                    </div>
                                                    <div class="mb-3">
                                                        <input type="hidden" id="no_max_day" name="no_max_day">
                                                        <label for="actualbata" class="form-label">No of Date</label>
                                                        <input type="number" class="form-control" id="nOfDate" name="nOfDate" onkeyup="if(this.value > Number($('#no_max_day').text())) this.value = Number($('#no_max_day').text());">
                                                    </div>
                                                    <div class="mb-3">
                                                        <!-- <input type="hidden" id="preiu_rate" name="preiu_rate"> -->
                                                        <label for="vehicleRate" class="form-label">Vehicle Rate</label>
                                                        <input type="number" class="form-control" id="vehicleRate" name="vehicleRate">
                                                        <!-- <input type="number" class="form-control" id="vehicleRate" name="vehicleRate" onkeyup="if(this.value > Number($('#preiu_rate').val())) this.value = Number($('#preiu_rate').val());"> -->
                                                    </div>
                                                </form>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                <button type="button" class="btn btn-success" id="saveDetails">Save</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Modal -->
                                <!-- <div class="modal fade" id="changeBataModal" tabindex="-1" aria-labelledby="editTransportModalLabel" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="editTransportModalLabel">Add Bata</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">

                                                <form id="editTransportForm">
                                                    <div class="mb-3">
                                                        <label for="actualbata" class="form-label">Actual Bata</label>
                                                        <input type="text" class="form-control" id="actualbata" name="actualbata" value="">
                                                    </div>
                                                </form>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                <button type="button" class="btn btn-success" id="saveBataDetails">Save</button>
                                            </div>
                                        </div>
                                    </div>
                                </div> -->

                            </div> <!-- end .h-100-->

                        </div> <!-- end col -->
                    </div>

                </div>
                <!-- container-fluid -->
            </div>
            <!-- End Page-content -->

            <?= view('layouts/brand') ?>

        </div>
        <!-- END layout-wrapper -->

        <?= view('layouts/inpage_footer') ?>
        <?= view('layouts/theme_settings') ?>
        <?= view('layouts/scripts') ?>

        <script>
            $('.js-example-basic-single').select2({
                placeholder: " Select",
                allowClear: true
            });


            $(document).ready(function() {
                var tour_id = $("#tour_id").val();
                load_advance_payment_history();
                populateAddedInvoice(tour_id);
                balance_payment(tour_id)
                // getAgentAbsoluteBalance(agent);
            });

            // change agent
            $("#agent").on('change', function(e) {
                load_advance_payment_history();
                // getAgentAbsoluteBalance(agent);
            });

            // change tagent
            $("#tagent").on('change', function(e) {
                var tour_id = $("#tour_id").val();
                populateAddedInvoice(tour_id);
                // getAgentAbsoluteBalance(agent);
            });

            function get_logsheet_mileage(tour_id, agent) {
               
                $.ajax({
                    url: '<?php echo base_url('tour/booking_confirm/get_logsheet_mileage/') ?>' + tour_id + '/' + agent,
                    type: 'GET',
                    dataType: 'json',
                    success: function(data) {
                        if (data) {
                            $('#total_mile').text(data.estimate_mileage);
                            $('#vehicle_rate').text(data.vehicle_rate);
                            $('#total_actual_mile').text(parseFloat(data.log_sheet_mileage).toFixed(2));
                            $('#total_mile_rate').text(data.log_sheet_mileage * data.vehicle_rate);
                            $('#per_dat_rate').text(data.per_day_rate);
                            $('#no_od_date').text(data.no_of_dates);
                            $('#total_bata').text(data.per_day_rate * data.no_of_dates);

                            //  var noOfDates=parseInt(text(data.no_of_dates));
                           

                            
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'An error occured while processing the request',
                        });
                    }
                });
            }

            function get_nn(tour_id,agent,noOfDates) {
                noOfDates = parseInt(noOfDates);
                 
                // alert("sdgfsdgdg");
                $.ajax({
                    url: '<?php echo base_url('tour/booking_confirm/get_tot_day') ?>',
                    type: 'POST',
                    data: {
                        tour_id: tour_id,
                        agent: agent
                    },
                    dataType: 'json',
                    success: function(res) {
                        if (res.status === 200) {
                            var tot = parseInt(res.total_days);
                            var maxDays = noOfDates - tot;
                            $('#no_max_day').text(maxDays);
                            
                        }
                    }
                })
            }

            // change transagent
            $("#transagent").on('change', function(e) {
                var tour_id = $("#tour_id").val();
                var agent = $("#transagent").val();
                populateAddedInvoice(tour_id);
                // getAgentAbsoluteBalance(agent);
                balance_payment(tour_id)
                get_logsheet_mileage(tour_id, agent);
                getAgentAbsoluteBalance(agent);
            });

            // Load Advance Payment History
            function load_advance_payment_history() {
                var agent = $("#agent").val();
                var tour_id = $("#tour_id").val();

                $.ajax({
                    url: '<?php echo base_url('tour/booking_confirm/payment_history') ?>',
                    type: 'GET',
                    data: {
                        tour_id: tour_id,
                        agent: agent
                    },
                    dataType: 'json',
                    success: function(data) {
                        if (data.status == 200) {
                            var html = '';
                            var i = 1;
                            $("#payment_history").empty();
                            data.data.tour_details.forEach(function(value, key) {
                                value = value[0] || null;
                                // console.log(value);
                                if (value != null) {

                                    var agent = value.New_L_Code ? value.New_L_Code : '';
                                    html += '<tr>';
                                    html += '<td>' + i + '</td>';
                                    html += '<td>' + value.Date + '</td>';
                                    html += '<td>' + agent + '</td>';
                                    html += '<td>' + value.Recpt_No + '</td>';
                                    html += '<td>' + value.Discription + '</td>';
                                    html += '<td>' + value.Dr + '</td>';
                                    html += '</tr>';
                                    i++;
                                }
                            });

                            $("#payment_history").html(html);

                            // check tour_details length
                            if (data.data.tour_details.length > 0) {
                                $(".history_table").removeClass('d-none');
                                $(".table_title").removeClass('d-none');
                            } else {
                                $(".history_table").addClass('d-none');
                                $(".table_title").addClass('d-none');
                            }

                            var htmlp = '';
                            var j = 1;
                            $("#pending_vouchers").empty();
                            $.each(data.data.voucherpayment, function(key, value) {
                                var status = value.Aflag;
                                if (status == 0) {
                                    status = 'Pending';
                                } else if (status == 1) {
                                    status = 'Paid';
                                } else if (status == 2) {
                                    status = 'Rejected';
                                } else {
                                    status = 'Cancelled';
                                }
                                htmlp += '<tr>';
                                htmlp += '<td>' + j + '</td>';
                                htmlp += '<td>' + value.Pay + '</td>';
                                htmlp += '<td>' + value.Bank + '</td>';
                                htmlp += '<td>' + value.Vnum + '</td>';
                                htmlp += '<td>' + value.Amount + '</td>';
                                htmlp += '<td>' + status + '</td>';
                                htmlp += '</tr>';
                                j++;
                            });

                            $("#pending_vouchers").html(htmlp);
                            // check voucherpayment length
                            if (data.data.voucherpayment.length > 0) {
                                $(".pending_table").removeClass('d-none');
                                $(".table_text_voucher").removeClass('d-none');
                            } else {
                                $(".pending_table").addClass('d-none');
                                $(".table_text_voucher").addClass('d-none');
                            }

                        } else {

                            console.error(data.message ? data.message : 'An error occured while processing the request');

                        }
                    },
                    error: function() {
                        // Swal.fire({
                        //     icon: 'error',
                        //     title: 'Error',
                        //     text: 'An error occured while processing the request',
                        // });
                    }
                });

                // populateAddedInvoice(tour_id);
                // balance_payment(tour_id)
                // getAgentAbsoluteBalance(agent);
            }

            // Add Advance Payment
            $("#form_submit_advance").on('submit', function(e) {
                e.preventDefault();

                var formData = new FormData(this);
                var tour_id = $('#tour_id').val();
                var agent = $('#agent').val();
                var agent_name = $('#agent option:selected').text();
                var ref_no = $('#ref').val();
                var payment = $('#payment').val();
                var bank_acc = $('#bank_acc').val();
                var bank_name = $('#bank_acc option:selected').text();
                var advance_date = $('#advance_date').val();
                // get t_consignee value
                var t_consignee = $('#t_consignee').text();

                // check required fields
                if (agent == '' || ref_no == '' || payment == '' || bank_acc == '' || advance_date == '') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Please fill all required fields',
                    });
                    return;
                }

                formData.append('tour_id', tour_id);
                formData.append('agent', agent);
                formData.append('agent_name', agent_name);
                formData.append('ref_no', ref_no);
                formData.append('payment', payment);
                formData.append('bank_acc', bank_acc);
                formData.append('bank_name', bank_name);
                formData.append('advance_date', advance_date);
                formData.append('manu_id', t_consignee);

                $.ajax({
                    url: '<?php echo base_url('tour/booking_confirm/tour_transport') ?>',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(data) {
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
                                text: data.message ? data.message : 'An error occurred while processing the request',
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'An error occurred while processing the request',
                        });
                    }
                });
            });

            // Load Expenses Account based on Expenses Type 
            $('#category').change(function() {
                var category = $(this).val();
                $.ajax({
                    url: "<?php echo base_url() ?>accounts/creditor_invoice/get_details",
                    type: 'POST',
                    data: {
                        category: category
                    },
                    dataType: 'json',
                    success: function(response) {
                        // Handle the response data here
                        $('#supplier').empty();
                        $.each(response.data, function(index, value) {

                            $('#supplier').append('<option value=""></option>');
                            $('#supplier').append('<option value="' + value.L_Code + '">' + value.PName + '</option>');
                        });
                    },
                    error: function(xhr, status, error) {
                        // Handle any errors here
                    }
                });

                // if (category == '4-8-106-10385') {
                //     $('#ddlabel').text('KM *');
                //     $('#ddalabel').text('Per KM Rate');
                // } else {
                //     $('#ddlabel').text('Doller Rate *');
                //     $('#ddalabel').text('$ Amount');
                // }

            });

            // Calculate LKR Amount
            $("#amount").keyup(function() {
                var d_rate = parseFloat($('#dollerrate').val());
                var dolleramount = parseFloat($(this).val());
                var result = d_rate * dolleramount;
                $('#rsamount').val(result)
            });

            function form_submit(e) {
                var formData = new FormData(e);
                var tour_id = $('#tour_id').val();
                var description = $('#t_consignee').text();
                formData.append('tour_id', tour_id);
                formData.append('description', description);
                formData.append('agent_name', $('#tagent option:selected').text());
                formData.append('supplier_name', $('#supplier option:selected').text());

                $.ajax({
                    url: "<?php echo base_url() ?>tour/booking_confirm/add_expenses",
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    cache: false,
                    dataType: 'json',
                    success: function(response) {
                        if (response.status == 200) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: response.message,
                            }).then((result) => {

                                populateAddedInvoice(tour_id);
                                balance_payment(tour_id)
                                // trigger chass_no change
                                // $('#category').val("").change();
                                $('#supplier').val("").change();
                                // $('#inv').val("");
                                // $('#date').val("");
                                $('#des').val("");
                                $('#amount').val("");
                                $('#rsamount').val("");
                                $('#save_btn').prop('disabled', false);
                            });
                        } else if (response.status == 400) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message,
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message,
                            });
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: 'Something went wrong! Please try again later.',
                        });

                    }
                });
            }

            // Add Expenses
            $('#form_submit').submit(function(e) {
                e.preventDefault();

                var tagent = $('#tagent').val();
                var current_transport_agent = $('#t_transportagent').text();
                if (current_transport_agent != '' && tagent != current_transport_agent) {
                    // confirm
                    Swal.fire({
                        title: 'Are you sure?',
                        text: "You changed the transport agent. Do you want to continue?",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes, change it!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            form_submit(this);
                        }
                    });
                } else {
                    form_submit(this);
                }

                // disble save button
                // $('#save_btn').prop('disabled', true);


            });

            // Delete Expenses
            function delete_expenses(id) {

                var tour_id = $('#tour_id').val();
                var is_settled = $('#is_settled').val();
                var id = id;

                Swal.fire({
                    title: 'Are you sure?',
                    text: "You want to delete this Expenses!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            type: "POST",
                            url: "<?php echo base_url(); ?>tour/booking_confirm/delete_expenses",
                            data: {
                                id: id,
                                tour_id: tour_id,
                                is_settled: is_settled
                            },
                            dataType: "json",
                            success: function(response) {
                                if (response.status == 200) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Success',
                                        text: response.message,
                                    }).then((result) => {
                                        if (result.isConfirmed) {
                                            populateAddedInvoice(tour_id);
                                            balance_payment(tour_id)
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
                            error: function(jqXHR, textStatus, errorThrown) {

                            }
                        });
                    }
                });
            }

            // Load Expenses Table 
            function populateAddedInvoice(tour_id) {
                var agent = $('#tagent').val();

                $.ajax({
                    url: "<?php echo base_url() ?>tour/booking_confirm/get_tour_expenses_details",
                    type: 'POST',
                    data: {
                        tour_id: tour_id,
                        agent: agent
                    },
                    dataType: 'json',
                    success: function(response) {
                        // Handle the response data here
                        var totalAmount = 0;
                        var total = 0;
                        $('#creditor_invoice_tbl').html('');
                        $.each(response.data, function(index, value) {
                            var job = value.job;
                            var flag = value.advances_pay;

                            $('#description').val(value.consignee);
                            // $('#tagent').val(value.agent).trigger('change');
                            // $('#bank_acc').val(value.bank).trigger('change');
                            payment_type = '';
                            if (job == '4-8-106-10386') {
                                payment_type = 'Hotel';
                            } else if (job == '4-8-106-10385') {
                                payment_type = 'Transport';
                            } else if (job == '6-9-23-10640') {
                                payment_type = 'Entrance Fee';
                            } else if (job == '6-9-23-10641') {
                                payment_type = 'Other';
                            }

                            if (flag == 1) {
                                trans = (value.Name || value.name);
                                sup = value.sup_name;
                            } else {
                                trans = '';
                                sup = value.sup_name;
                            }

                            $('#creditor_invoice_tbl').append(
                                '<tr>' +
                                '<td>' + value.tour_id + '</td>' +
                                '<td>' + value.consignee + '</td>' +
                                '<td>' + value.agent_name + '</td>' +
                                '<td>' + trans + '</td>' +
                                '<td>' + payment_type + '</td>' +
                                '<td>' + sup + '</td>' +
                                '<td>' + value.Invoice_Num + '</td>' +
                                '<td>' + value.Date + '</td>' +
                                '<td>' + value.Cr + '</td>' +
                                '<td><button type="button" class="btn btn-sm btn-danger" onClick="delete_expenses(' + value.ID + ')"><i class="bi bi-trash"></i></td>' +
                                '</tr>'
                            );
                        });
                        $.each(response.data, function(index, value) {
                            totalAmount += parseFloat(value.Cr);
                            total = totalAmount;
                        });
                        $('#creditor_invoice_tbl').append(
                            '<tr>' +
                            '<td colspan="8" class="text-right">Total Amount:</td>' +
                            '<td>' + parseFloat(total).toFixed(2) + '</td>' +
                            '</tr>'
                        );

                        // console.log(response.data.length);
                        if (response.data.length > 0) {
                            $('#added_creditor_invoice').removeClass('d-none');
                        } else {
                            $('#added_creditor_invoice').addClass('d-none');
                        }
                    },
                    error: function(xhr, status, error) {
                        // Handle any errors here
                    }
                });
            }

            // Load Balance Payment
            function balance_payment(tour_id) {
                var agent = $('#transagent').val();
                var agent_name = $('#transagent option:selected').text();

                // alert(agent_name);
                $.ajax({
                    url: "<?php echo base_url() ?>tour/booking_confirm/get_balance_payment_details",
                    type: 'POST',
                    data: {
                        tour_id: tour_id,
                        agent: agent,
                        agent_name: agent_name
                    },
                    dataType: 'json',
                    success: function(response) {
                        // Handle the response data here
                        var dr = response.data.advance_dr ? response.data.advance_dr : 0; // 50000
                        var cr = response.data.expenses_cr ? response.data.expenses_cr : 0; // -55
                        var ad_remain_cr = response.data.ad_remain_cr ? response.data.ad_remain_cr : 0;
                        var total_transport_cost = response.data.total_transport_cost ? response.data.total_transport_cost : 0;
                        var total_pay_cr = response.data.total_pay_cr ? response.data.total_pay_cr : 0;
                        var totalAmount = parseFloat(dr) - (parseFloat(cr));
                        var total = parseFloat(totalAmount).toFixed(2);

                        $('#tot_trans_cost').val(total_transport_cost); 
                        // $('#total_mile').text(response.data.estimate_mileage);
                        $('#total_mile').text(response.data.total_mileage);
                        $('#total_actual_mile').text(response.data.log_sheet_mileage);
                        $('#totalactualmileage').val(response.data.log_sheet_mileage);
                        $('#actualbata').val(response.data.bata_day_rate);
                        $('#nOfDate').val(response.data.bata_days);
                        $('#vehicleRate').val(response.data.vehicle_rate);

                        $('#vehicle_rate').text(response.data.vehicle_rate);

                        if (response.data.log_sheet_mileage !== null && response.data.log_sheet_mileage !== '') {
                            $('#total_mile_rate').text(Number(response.data.log_sheet_mileage) * Number(response.data.vehicle_rate));
                        } else {
                            $('#total_mile_rate').text(Number(response.data.total_mileage) * Number(response.data.vehicle_rate));
                        }

                        $('#per_dat_rate').text(response.data.bata_day_rate);
                        $('#no_od_date').text(response.data.bata_days);
                        $('#total_bata').text(response.data.bata_day_rate * response.data.bata_days);

                         var noOfDates=response.data.bata_days;
                         get_nn(tour_id,agent,noOfDates);

                        $('#total_transport_cost2').val(total_transport_cost);

                        var pay = (parseFloat(total_transport_cost) - parseFloat(dr)) + parseFloat(cr) - parseFloat(total_pay_cr);
                        var pay_bal_amt = parseFloat(pay).toFixed(2);
                        $('#cash').val(pay_bal_amt);

                        $('#remaining_balance').val(total);

                        $('#balance_payment').html('');

                        $('#balance_payment').append(
                            '<tr>' +
                            '<td> ' + total_transport_cost + '</td>' +
                            '<td>- (' + dr + '</td>' +
                            '<td> - ' + (parseFloat(cr)) + '</td>' +
                            '<td> = ' + total + ')</td>' +
                            '<td> - ' + parseFloat(total_pay_cr) + '</td>' +
                            '<td> = ' + pay_bal_amt + '</td>' +
                            '</tr>'
                        );

                        // setted_pay_voucher_history table
                        var html = '';
                        var i = 1;
                        $("#setted_pay_voucher_history").empty();

                        $.each(response.voucherpayment, function(key, value) {
                            var status = value.Aflag;
                            if (status == 0) {
                                status = '<span class="badge bg-warning text-dark">Pending</span>';
                            } else if (status == 1) {
                                status = '<span class="badge bg-success text-white">Paid</span>';
                            } else if (status == 2) {
                                status = '<span class="badge bg-danger text-white">Rejected</span>';
                            } else {
                                status = '<span class="badge bg-secondary text-white">Cancelled</span>';
                            }
                            html += '<tr>';
                            html += '<td>' + i + '</td>';
                            html += '<td>' + value.W_Date + '</td>';
                            html += '<td>' + value.Pay + '</td>';
                            html += '<td>' + value.Vnum + '</td>';
                            html += '<td>' + status + '</td>';
                            html += '<td>' + value.Amount + '</td>';
                            html += '</tr>';
                            i++;
                        });

                        $("#setted_pay_voucher_history").html(html);

                    },
                    error: function(xhr, status, error) {
                        // Handle any errors here
                    }
                });
            }

            function getAgentAbsoluteBalance(transagent) {
                $.ajax({
                    url: "<?php echo base_url() ?>tour/booking_confirm/get_t_agent_absolute_balance",
                    type: 'GET',
                    data: {
                        tagent: transagent,
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response) {
                            var amnt = response.data ? response.data : 0.00;
                            amnt = parseFloat(amnt).toFixed(2);
                            $('#transagent_over_paid_input').val(amnt);
                            $('#transagent_over_paid').text(parseFloat(amnt).toFixed(2));
                            $('#from_overpaid_amount').val(0);
                        }
                    }
                });
            }

            // .from_overpaid_amount change - must be less than over paid amount
            $('#from_overpaid_amount').keyup(function() {
                var over_paid = parseFloat($('#transagent_over_paid_input').val());
                var from_overpaid_amount = parseFloat($(this).val());
                if (from_overpaid_amount > over_paid || isNaN(from_overpaid_amount)) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Over paid amount must be less than or equal to ' + over_paid,
                    });
                    $(this).val(0);
                }
            });

            $("#form_submit_balance").on('submit', function(e) {
                // if (($('#finalize_txt').val()) == 0) {

                    var formData = new FormData(this);
                    var tour_id = $('#tour_id').val();
                    // get transagent text value in selected option
                    var transagent = $('#transagent').val();
                    var transagent_name = $('#transagent option:selected').text();
                    var pay_metghod = $('#bank_account option:selected').text();
                    var t_consignee = $('#t_consignee').text();
                    // append tour_id to form data
                    formData.append('tour_id', tour_id);
                    formData.append('transagent', transagent);
                    formData.append('pay_metghod', pay_metghod);
                    formData.append('transagent_name', transagent_name);
                    formData.append('mamual_id', t_consignee);

                    $.ajax({
                        url: "<?php echo base_url(); ?>tour/booking_confirm/setteled_payment",
                        type: "POST",
                        dataType: "json",
                        data: formData,
                        contentType: false,
                        cache: false,
                        processData: false,
                        success: function(data) {
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
                        }
                    });
                // } else {
                //     Swal.fire({
                //         icon: 'error',
                //         title: 'Error',
                //         text: 'Please finalize the quotation before confirming the hotel reservation.',
                //     });
                // }
            });

            // Edit Total Transport Cost
            $("#edit_total_transport").on('submit', function(e) {
                e.preventDefault();

                if ($('#tot_trans_cost').val() < $('#total_transport_cost2').val()) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Please enter total transport cost',
                    });
                    return false;
                }

                var formData = new FormData(this);
                var tour_id = $('#tour_id').val();
                formData.append('tour_id', tour_id);

                formData.append('total_mile', $('#total_mile').text());
                formData.append('vehicle_rate', $('#vehicle_rate').text());
                formData.append('total_mile_rate', $('#total_mile_rate').text());
                formData.append('per_dat_rate', $('#per_dat_rate').text());
                formData.append('no_od_date', $('#no_od_date').text());
                formData.append('total_bata', $('#total_bata').text());

                $.ajax({
                    url: "<?php echo base_url(); ?>tour/booking_confirm/edit_total_transport",
                    type: "POST",
                    dataType: "json",
                    data: formData,
                    contentType: false,
                    cache: false,
                    processData: false,
                    success: function(data) {

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
                    }
                });
            });

            // Add Logsheet Mileage
            $('#saveDetails').on('click', function() {
                var tour_id = $('#tour_id').val();
                var totalactualmileage = $('#totalactualmileage').val();
                var bata = $('#actualbata').val();
                var agent = $('#transagent').val();
                var nOfDate = $('#nOfDate').val();
                var v_rate = $('#vehicleRate').val();

                // alert(bata);

                if (bata != '') {
                    ac_bata = bata;
                } else {
                    ac_bata = $('#per_dat_rate').text();
                }

                if(totalactualmileage == '') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Please enter actual mileage',
                    });
                    return false;
                }

                $('#total_acmile').text(totalactualmileage);
                $('#logSheettModal').modal('hide');

                $.ajax({
                    url: "<?php echo base_url(); ?>tour/booking_confirm/add_logsheet_mileage",
                    type: "POST",
                    dataType: "json",
                    data: {
                        tour_id: tour_id,
                        agent: agent,
                        estimatedmileage: $('#total_mile').text(),
                        totalactualmileage: totalactualmileage,
                        v_rate: $('#vehicle_rate').text(),
                        dara_rate: ac_bata,
                        noofdate: nOfDate,
                        v_rate_new: v_rate,
                    },
                    success: function(data) {
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
                    }
                });
            });

            // Add Logsheet Mileage
            // $('#saveBataDetails').on('click', function() {
            //     var tour_id = $('#tour_id').val();
            //     var actualbata = $('#actualbata').val();
            //     var agent = $('#transagent').val();

            //     // $('#total_acmile').text(actualbata);
            //     $('#changeBataModal').modal('hide');

            //     $.ajax({
            //         url: "<?php echo base_url(); ?>tour/booking_confirm/add_logsheet_mileage",
            //         type: "POST",
            //         dataType: "json",
            //         data: {
            //             tour_id: tour_id,
            //             agent: agent,
            //             estimatedmileage: $('#total_mile').text(),
            //             actualbata: actualbata,
            //             v_rate: $('#vehicle_rate').text(),
            //             dara_rate: $('#per_dat_rate').text(),
            //             noofdate: $('#no_od_date').text(),
            //         },
            //         success: function(data) {
            //             if (data.status == 200) {
            //                 Swal.fire({
            //                     icon: 'success',
            //                     title: 'Success',
            //                     text: data.message,
            //                 }).then((result) => {
            //                     if (result.isConfirmed) {
            //                         location.reload();
            //                     }
            //                 });
            //             } else {
            //                 Swal.fire({
            //                     icon: 'error',
            //                     title: 'Error',
            //                     text: data.message ? data.message : 'An error occured while processing the request',
            //                 });
            //             }
            //         }
            //     });
            // });
        </script>

</body>




</html>