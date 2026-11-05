<table class="table table-hover js-basic-example dataTable table-custom">
    <thead>
        <tr>
            <th>ID</th>
            <th>Item Code</th>
            <th>Name</th>
            <th>Qty</th>
            <th>Sub Total</th>
            <th>Retail Price</th>
            <th>Wholesale Price</th>
            <th>Remove</th>
        </tr>
    </thead>
    <tbody>

        <?php
        $count = 0;
        $tot_sale = 0;
        foreach ($itemDetails as $value) {
            $count++;
            $tot_sale += ($value->qty * $value->Dis_price);
        ?>
            <tr>
                <td class="mailbox-name"> <?php echo $count; ?>.</td>
                <td>
                    <?php
                    if ($value->SN == NULL || $value->SN == '') {
                        echo $value->stock_card;
                    } else {
                        echo $value->stock_card . " (" . $value->SN . ")";
                    }
                    ?>
                </td>
                <td><?php echo $value->Description; ?></td>
                <td align="center">
                    <?php if ($value->SN == NULL || $value->SN == '') { ?>
                        <input type="text" value="<?php echo $value->qty; ?>" data-row-id="<?php echo $value->ID; ?>" data-qty="<?php echo $value->qty; ?>" data-price="<?php echo $value->ucost; ?>" class="form-control btn_edit txt_nav1" width="50%" id="qty_<?php echo $value->ID; ?>" style="width: 70px;"> &nbsp;<small class="errorDiv" style="display:none;color: red" id="msg_<?php echo $value->ID; ?>">Invalid Qty</small>
                    <?php } else {
                        echo $value->qty;
                    } ?>

                </td>
                <td align="right"><?php echo $value->foc; ?></td>
                <td align="right"><?php echo number_format($value->ucost, 2); ?></td>
                <td align="right" id="pr_<?php echo $value->ID; ?>" class="s_val">
                    <?php $sale_val = $value->ucost;
                    echo number_format($sale_val, 2); ?>
                </td>
                
                <td align="right"><?php echo number_format($value->rtl_min, 2); ?></td>
                <td align="right"><?php echo number_format($value->rtl_max, 2); ?></td>
                <td align="center">
                    <button value="<?php echo $value->ID; ?>" class="btn btn-info btn-sm btn_dell"><span class="glyphicon glyphicon-trash"></span></button>
                </td>
            </tr>
        <?php
        }
        ?>
    </tbody>
</table>
<?php if (count($itemDetails) > 0) { ?>
    <button class="btn btn-success" id="del_all">REMOVE ALL</button>
    <h4 class="sval pull-right">Total Order Value: &nbsp;Rs. <span class="sval" id="tot_val"><?php echo number_format($tot_sale, 2); ?></span></h4>
    <hr>
    <button class="btn btn-danger" data-toggle="modal" data-target="#edit_data">FINALIZE</button>
<?php } ?>