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
                                    <div class="col-md-4 d-flex">
                                        <h4 class="p-title">Vehicle</h4>

                                    </div>

                                </div>

                                <div class="row clearfix mt-3">
                                    <div class="col-md-4">
                                        <div class="card p-4">
                                            <div class="body">
                                                <form action="javascript:void(0);" id="form_submit" accept-charset="utf-8">
                                                    <fieldset>

                                                        <div class="mb-3">
                                                            <label class="form-label" for="type">Type<font color="#FF0000"><strong>*</strong></font></label>
                                                            <input type="text" name="type" id="type" class="js-example-basic-single form-control" style="width: 100%;" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label" for="vnu_up">Vehicle Number<font color="#FF0000"><strong>*</strong></font></label>
                                                            <input type="text" name="vnu" id="vnu" class="js-example-basic-single form-control" style="width: 100%;" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label" for="vowner">Owner<font color="#FF0000"><strong>*</strong></font></label>
                                                            <select name="vowner" id="vowner" class="form-control" required>
                                                                <option value="">Select</option>
                                                                <?php
                                                                foreach ($suppliers as $sup) {
                                                                    echo "<option value='" . $sup['Sup_Acc_No'] . "'>" . $sup['Name'] . "</option>";
                                                                }
                                                                ?>
                                                            </select>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label" for="vownercontact">Owner Contact Number<font color="#FF0000"><strong>*</strong></font></label>
                                                            <input type="text" name="vownercontact" id="vownercontact" class="js-example-basic-single form-control" style="width: 100%;" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label" for="passenger_count">No. of Passengers<font color="#FF0000"><strong>*</strong></font></label>
                                                            <input class="form-control" type="text" id="passenger_count" name="passenger_count" placeholder="" required />
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label" for="km_rate">Km/Hour Rate <font color="#FF0000"><strong>*</strong></font></label>
                                                            <input class="form-control" type="text" id="km_rate" name="km_rate" placeholder="" required />
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label" for="bata_per_day">Bata Per Day</label>
                                                            <input class="form-control" type="text" id="bata_per_day" name="bata_per_day" placeholder="" />
                                                        </div>
                                                        <div class="mb-3">
                                                            <div class="form-check">
                                                                <label class="form-check-label" for="rate_vehicle">Is Rate Vehicle</label>
                                                                <input class="form-check-input" type="checkbox" name="rate_vehicle" id="rate_vehicle">
                                                            </div>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label" for="vimg">Vehicle Image</label>
                                                            <input class="form-control" type="file" id="vimg" name="vimg" placeholder="" value="" />
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label" for="vlimg">Vehicle License Image</label>
                                                            <input class="form-control" type="file" id="vlimg" name="vlimg" placeholder="" value="" />
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label" for="vled">Vehicle License Expire Date</label>
                                                            <input class="form-control" type="date" id="vled" name="vled" placeholder="" value="" />
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label" for="viimg">Vehicle Insurance Image</label>
                                                            <input class="form-control" type="file" id="viimg" name="viimg" placeholder="" value="" />
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label" for="vied">Vehicle Insurance Expire Date</label>
                                                            <input class="form-control" type="date" id="vied" name="vied" placeholder="" value="" />
                                                        </div>
                                                        <br>
                                                        <button type="submit" class="btn btn-success w-100px me-5px">Save</button>
                                                    </fieldset>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-8">
                                        <div class="card p-4">
                                            <div class="body">
                                                <div class="table-responsive">
                                                    <table class="table js-basic-example table-hover dataTable table-custom align-middle">
                                                        <thead class="thead-dark">
                                                            <tr>
                                                                <th>#</th>
                                                                <th>Vehicle Type</th>
                                                                <th>V Number</th>
                                                                <th>Owner</th>
                                                                <th>Owner Contact Num</th>
                                                                <th>No. of Passengers</th>
                                                                <th>Km/Hour Rate</th>
                                                                <th>Bata per day</th>
                                                                <th>Vehicle Image</th>
                                                                <th>License Image</th>
                                                                <th>License Expire Date</th>
                                                                <th>Insurance Image</th>
                                                                <th>Insurance Expire Date</th>
                                                                <th>Action</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php
                                                            $i = 1;
                                                            foreach ($vehicles as $row) {
                                                            ?>
                                                                <tr>
                                                                    <td><?= $i++; ?></td>
                                                                    <td><?= $row['type']; ?></td>
                                                                    <td><?= $row['v_num']; ?></td>
                                                                    <td><?= $row['Name']; ?></td>
                                                                    <td><?= $row['ocontact']; ?></td>
                                                                    <td><?= $row['passenger_count']; ?></td>
                                                                    <td><?= $row['km_rate']; ?></td>
                                                                    <td><?= $row['bata']; ?></td>
                                                                    <td><?php if ($row['vimg'] == '') { ?>
                                                                            <img src="<?php echo base_url("uploads/places/no-image.png"); ?>" style="max-width: 100px; max-height: 100px;">
                                                                        <?php } else { ?>
                                                                            <img src="<?php echo base_url("uploads/vehicle/" . $row['vimg']); ?>" alt="<?php echo $row['vimg']; ?>" style="max-width: 100px; max-height: 100px;">
                                                                        <?php } ?>
                                                                    </td>
                                                                    <td><?php if ($row['vlimg'] == '') { ?>
                                                                            <img src="<?php echo base_url("uploads/places/no-image.png"); ?>" style="max-width: 100px; max-height: 100px;">
                                                                        <?php } else { ?>
                                                                            <img src="<?php echo base_url("uploads/vehicle/license/" . $row['vlimg']); ?>" alt="<?php echo $row['vlimg']; ?>" style="max-width: 100px; max-height: 100px;">
                                                                        <?php } ?>
                                                                    </td>
                                                                    <td><?= $row['vehi_license_expire_date']; ?></td>
                                                                    <td><?php if ($row['viimg'] == '') { ?>
                                                                            <img src="<?php echo base_url("uploads/places/no-image.png"); ?>" style="max-width: 100px; max-height: 100px;">
                                                                        <?php } else { ?>
                                                                            <img src="<?php echo base_url("uploads/vehicle/insurance/" . $row['viimg']); ?>" alt="<?php echo $row['viimg']; ?>" style="max-width: 100px; max-height: 100px;">
                                                                        <?php } ?>
                                                                    </td>
                                                                    <td><?= $row['vehi_insurance_expire_date']; ?></td>
                                                                    <td>
                                                                        <button type="button" class="btn btn-secondary btn-sm" data-toggle="tooltip" title="Edit" onClick='editVehicle(<?= $row['id']; ?>)' data-bs-toggle='modal' data-bs-target='#editModal'><i class="bi bi-pencil"></i></button>
                                                                        <button type="button" class="btn btn-danger btn-sm v_remove" data-toggle="tooltip" title="Delete" data-rowid="<?= $row['id'] ?>"><i class="bi bi-trash"></i></button>
                                                                    </td>
                                                                </tr>
                                                            <?php
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

        <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="editModalLabel">Edit Vehicle</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form action="javascript:void(0);" id="update_form" accept-charset="utf-8">
                            <input type="hidden" name="row_id" id="row_id">
                            <div class="row">
                                <div class="mb-4">
                                    <div class="mb-3">
                                        <label class="form-label" for="type_up">Type<font color="#FF0000"><strong>*</strong></font></label>
                                        <input type="text" name="type_up" id="type_up" class="js-example-basic-single form-control" style="width: 100%;" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label" for="vnu_up">Vehicle Number<font color="#FF0000"><strong>*</strong></font></label>
                                        <input type="text" name="vnu_up" id="vnu_up" class="js-example-basic-single form-control" style="width: 100%;" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label" for="vowner_up">Owner<font color="#FF0000"><strong>*</strong></font></label>
                                        <!-- <input type="text" name="vowner_up" id="vowner_up" class="js-example-basic-single form-control" style="width: 100%;" required> -->
                                        <select class="form-control" name="vowner_up" id="vowner_up" style="width: 100%;" required>
                                            <option value="">Select</option>
                                            <?php
                                            foreach ($suppliers as $sup) {
                                                echo "<option value='" . $sup['Sup_Acc_No'] . "'>" . $sup['Name'] . "</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label" for="vownercontact_up">Owner Contact Number<font color="#FF0000"><strong>*</strong></font></label>
                                        <input type="text" name="vownercontact_up" id="vownercontact_up" class="js-example-basic-single form-control" style="width: 100%;" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label" for="passenger_count_up">No. of Passengers<font color="#FF0000"><strong>*</strong></font></label>
                                        <input class="form-control" type="text" id="passenger_count_up" name="passenger_count_up" placeholder="" required />
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label" for="km_rate_up">Km/Hour Rate <font color="#FF0000"><strong>*</strong></font></label>
                                        <input class="form-control" type="text" id="km_rate_up" name="km_rate_up" placeholder="" required />
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label" for="bata_per_day_up">Bata Per Day</label>
                                        <input class="form-control" type="text" id="bata_per_day_up" name="bata_per_day_up" placeholder="" />
                                    </div>
                                    <div class="mb-3">
                                        <div class="form-check">
                                            <label class="form-check-label" for="rate_vehicle">Is Rate Vehicle</label>
                                            <input class="form-check-input" type="checkbox" name="rate_vehicle_up" id="rate_vehicle_up">
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label" for="vimg">Vehicle Image</label>
                                        <input class="form-control" type="file" id="vimg" name="vimg" placeholder="" value="" />
                                        <img class="mt-2" src="" id="vimg_image" style="max-width: 100px; max-height: 100px;">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label" for="vlimg">Vehicle License Image</label>
                                        <input class="form-control" type="file" id="vlimg" name="vlimg" placeholder="" value="" />
                                        <img class="mt-2" src="" id="vlimg_image" style="max-width: 100px; max-height: 100px;">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label" for="vled_up">Vehicle License Expire Date</label>
                                        <input class="form-control" type="date" id="vled_up" name="vled_up" placeholder="" value="" />
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label" for="viimg">Vehicle Insurance Image</label>
                                        <input class="form-control" type="file" id="viimg" name="viimg" placeholder="" value="" />
                                        <img class="mt-2" src="" id="viimg_image" style="max-width: 100px; max-height: 100px;">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label" for="vied_up">Vehicle Insurance Expire Date</label>
                                        <input class="form-control" type="date" id="vied_up" name="vied_up" placeholder="" value="" />
                                    </div>
                                    <button type="submit" class="btn btn-success mt-2 update_vehicle" id="update_vehicle">Save</button>
                                </div>

                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

    </div>
    <!-- END layout-wrapper -->

    <?= view('layouts/inpage_footer') ?>
    <?= view('layouts/theme_settings') ?>
    <?= view('layouts/scripts') ?>

    <script>
        // function getowner(id){
        //     var acc = id;
        //     $.ajax({
        //         url: "<?php //echo base_url(); 
                            ?>master_files/supplier/get_supplier_data/" + acc,
        //         type: "GET",
        //         dataType: "json",
        //         success: function(response) {
        //             if (response) {
        //                 $("#vownercontact").val(response.rep_mobile);
        //             }
        //         }
        //     });
        // }
        $(document).ready(function() {
            // Submit form data via Ajax
            $('#form_submit').submit(function(e) {
                e.preventDefault();

                var formdata = new FormData(this);
                if ($('#rate_vehicle').is(':checked')) {
                    formdata.append('action', 1);
                } else {
                    formdata.append('action', 0);
                }

                $.ajax({
                    url: "<?php echo base_url() ?>master_files/vehicle/create",
                    type: 'POST',
                    data: formdata,
                    processData: false,
                    contentType: false,
                    cache: false,
                    dataType: 'json',
                    beforeSend: function() {
                        $(".btn-success").attr("disabled", true);
                        $(".btn-success").html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...');
                    },
                    success: function(response) {
                        $(".btn-success").attr("disabled", false);
                        $(".btn-success").html('Save');
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
                        $(".btn-success").attr("disabled", false);
                        $(".btn-success").html('Save');
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: 'Something went wrong! Please try again later.',
                        });

                    }
                });
            });

            $(".v_remove").on('click', function(e) {
                e.preventDefault();

                var row_id = $(this).data('rowid');

                // Confirm alert
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You want to delete this vehicle!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // deleteVehicle(row_id);
                        $.ajax({
                            url: "<?php echo base_url() ?>master_files/vehicle/deleteVehicle",
                            type: 'GET',
                            data: {
                                row_id: row_id,
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
                            error: function(jqXHR, textStatus, errorThrown) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Oops...',
                                    text: 'Something went wrong! Please try again later.',
                                });

                            }
                        });
                    }
                });

            });

            $("#update_form").submit(function(e) {
                e.preventDefault();

                var formdata = new FormData(this);
                if ($('#rate_vehicle_up').is(':checked')) {
                    formdata.append('action_up', 1);
                } else {
                    formdata.append('action_up', 0);
                }

                $.ajax({
                    url: "<?php echo base_url() ?>master_files/vehicle/update",
                    type: 'POST',
                    data: formdata,
                    processData: false,
                    contentType: false,
                    cache: false,
                    dataType: 'json',
                    beforeSend: function() {
                        $(".update_vehicle").attr("disabled", true);
                        $(".update_vehicle").html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...');
                    },
                    success: function(response) {
                        $(".update_vehicle").attr("disabled", false);
                        $(".update_vehicle").html('Save');
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
                    error: function(jqXHR, textStatus, errorThrown) {
                        $(".update_vehicle").attr("disabled", false);
                        $(".update_vehicle").html('Save');
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: 'Something went wrong! Please try again later.',
                        });

                    }
                });
            });

        });



        function editVehicle(index) {

            $("#editModal").show();

            $("#row_id").val(index);

            $.ajax({
                url: "<?php echo base_url(); ?>master_files/vehicle/show/" + index,
                type: "GET",
                dataType: "json",
                success: function(response) {

                    if (response.status == 200) {
                        $("#type_up").val(response.data.type);
                        $("#passenger_count_up").val(response.data.passenger_count);
                        $("#km_rate_up").val(response.data.km_rate);
                        $("#vnu_up").val(response.data.v_num);
                        $("#vowner_up").val(response.data.owner);
                        $("#vownercontact_up").val(response.data.ocontact);
                        $("#bata_per_day_up").val(response.data.bata);
                        if (response.data.action == 1) {
                            $("#rate_vehicle_up").prop('checked', true);
                        } else {
                            $("#rate_vehicle_up").prop('checked', false);
                        }
                        $("#vled_up").val(response.data.vehi_license_expire_date);
                        $("#vied_up").val(response.data.vehi_insurance_expire_date);
                        console.log(response.data.vimg);
                        console.log(response.data.vlimg);
                        console.log(response.data.viimg);
                        if (response.data.vimg != '' || response.data.vimg != null) {
                            $("#vimg_image").attr('src', "<?php echo base_url('uploads/vehicle/'); ?>" + response.data.vimg);
                        } else {
                            $("#vimg_image").css('display', 'none');
                        }
                        if (response.data.vlimg != '' || response.data.vlimg != null) {
                            $("#vlimg_image").attr('src', "<?php echo base_url('uploads/vehicle/license/'); ?>" + response.data.vlimg);
                        } else {
                            $("#vlimg_image").css('display', 'none');
                        }
                        if (response.data.viimg != '' || response.data.viimg != null) {
                            $("#viimg_image").attr('src', "<?php echo base_url('uploads/vehicle/insurance/'); ?>" + response.data.viimg);
                        } else {
                            $("#viimg_image").css('display', 'none');
                        }
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message,
                        });
                    }
                }
            });
        }

        // #vowner change
        $('#vowner').change(function() {
            var acc = $(this).val();
            $.ajax({
                url: "<?php echo base_url(); ?>master_files/supplier/get_supplier_data/" + acc,
                type: "GET",
                dataType: "json",
                success: function(response) {
                    if (response) {
                        $("#vownercontact").val(response.rep_offce || response.rep_mobile || response.Mobile);
                    }
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
        var $t = jQuery('table.dataTable');
        if ($t.length && jQuery.fn.dataTable && jQuery.fn.dataTable.isDataTable($t[0])) { clearInterval(t); $t.DataTable().search(q).draw(); }
        else if (++tries > 50) { clearInterval(t); }
    }, 100);
})();
</script>
</body>

</html>