<?php

namespace App\Controllers\Grn;

use App\Controllers\BaseController;

use App\Libraries\MenuLoader;
use App\Models\Grn\StockCardModel;
use App\Models\SupplierModel;
use App\Models\Grn\GrnTknOrderModel;
use App\Models\Grn\GrnTknModel;
use App\Models\InvoiceNumberModel as InvoiceNoModel;
use App\Models\Grn\StockCostModel;
use App\Models\Grn\StockModel;
use App\Models\Grn\StockOrderModel;

class ManualGrnController extends BaseController
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Colombo');
    }

    // route : base_url/document
    public function index()
    {
        $data = array();

        // Load menu data using the custom library
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        // Load the supplier data
        $supplierModel = new SupplierModel();
        $data['load_supplier'] = $supplierModel->where('sup_cat', 3)->findAll();

        $data['acc_no'] = session()->get('acc_no');

        //get first

        $inv =  $this->request->getGet('inv');
        $sup =  $this->request->getGet('sup');
        $tkn =  $this->request->getGet('tkn');

        if (!empty($inv) && !empty($sup) && !empty($tkn)) {
            $data['inv'] = $inv;
            $data['sup'] = $sup;
            $data['tkn'] = $tkn;

            return view('pages/grn/process_grn', $data);
        }

        return view('pages/grn/manual_grn', $data);
    }

    // route : base_url/inventory/manual_grn/get_pending?supp=:id
    public function getPendingGrnInvoices()
    {
        $supplierId = $this->request->getGet('supp');
        $grnModel = new GrnTknOrderModel();
        $pendingInvoices = $grnModel->where('sup_id', $supplierId)->where('flag', 0)->findAll();
        return json_encode($pendingInvoices);
    }

    // route : base_url/inventory/manual_grn/addGrnInvoice
    public function addGrnInvoice()
    {
        try {
            $app_date = $this->request->getPost('app_date');
            $inv_no = $this->request->getVar('inv_no');
            $supp_id = $this->request->getVar('user_id');

            $invoiceNumberModel = new InvoiceNoModel();
            $inv_id = $invoiceNumberModel->GetNextPurchaseNumber();

            $response = array();
            if (empty($app_date) || empty($inv_no) || empty($supp_id)) {
                return $response['status'] = 400;
            } else {

                $grnTknOrderModel = new GrnTknOrderModel();
                $is_exist = $grnTknOrderModel->where('order_no', $inv_no)->countAllResults();

                if ($is_exist > 0) {
                    $response['status'] = 409;
                    return json_encode($response);
                }

                $data_add = array(
                    'inv_no' => $inv_id,
                    'sup_id' => $supp_id,
                    'order_no' => $inv_no,
                    'date' => $app_date
                );

                $grnTknOrderModel->insert($data_add);

                return json_encode(array('status' => 200, 'tkn_no' => $inv_id, 'sup' => $supp_id));
            }
        } catch (\Exception $e) {
            return json_encode(array('status' => 500));
        }
    }

    // route : base_url/inventory/manual_grn/getSearch
    public function getSearch()
    {
        $keyword = $this->request->getGet('term');

        $sugg_json = array();
        $json_row = array();
        $key = preg_replace('/\s+/', ' ', $keyword);

        $stockCardModel = new StockCardModel();
        $bankArr = $stockCardModel->select("Descrip, Stock_Card")->like('Descrip', $key)->orderBy('Stock_Card')->limit(10)->findAll();

        foreach ($bankArr as $recResult) {
            $json_row["value"] = $recResult['Stock_Card'];
            $json_row["label"] = $recResult['Descrip'];
            array_push($sugg_json, $json_row);
        }

        return json_encode($sugg_json, JSON_UNESCAPED_SLASHES);
    }

    // route : base_url/inventory/manual_grn/get_dataset
    public function get_dataset()
    {

        $item = $this->request->getGet('item');
        try {
            $stockCardModel = new StockCardModel();
            $itemDetails = $stockCardModel->select('pp, Sales_P_Max, wPrice, SN')->where('Stock_Card', $item)->findAll();

            if ($itemDetails) {
                return json_encode(array('status' => 200, 'data' => $itemDetails));
            } else {
                return json_encode(array('status' => 400, 'message' => 'Item details not found.'));
            }
        } catch (\Exception $e) {
            return json_encode(array('status' => 500));
        }
    }

    // route : base_url/inventory/manual_grn/add_item
    public function add_item()
    {
        try {

            $supplier = $this->request->getPost('supplier');
            $tkn_no = $this->request->getPost('tkn_no');
            $item_code = $this->request->getPost('item_code');
            $quantity = $this->request->getPost('quantity');
            $foc_quantity = $this->request->getPost('foc_quantity') ?? 0;
            $p_price = $this->request->getPost('p_price');
            $total_u_cost = $this->request->getPost('total_u_cost');
            // $s_price = $this->request->getPost('s_price');
            $inv_no = $this->request->getPost('inv_no');
            // $w_price = $this->request->getPost('w_price');

            $validation = \Config\Services::validation();
            $validation->setRules([
                'supplier' => 'required|strip_tags',
                'tkn_no' => 'required|strip_tags',
                'item_code' => 'required|strip_tags',
                'quantity' => 'strip_tags',
                'foc_quantity' => 'strip_tags',
                'p_price' => 'strip_tags',
                'total_u_cost' => 'strip_tags',
                // 's_price' => 'strip_tags',
                'inv_no' => 'strip_tags',
                // 'w_price' => 'strip_tags',
            ]);

            // run validation with request
            if (!$validation->withRequest($this->request)->run()) {
                return json_encode(array('status' => 400, 'message' => 'Validation Error!', 'errors' => $validation->getErrors()));
            }

            $data_add = array(
                'sup' => $supplier,
                'inv' => $inv_no,
                'stock_card' => $item_code,
                'qty' => $quantity,
                'foc' => $foc_quantity,
                'ucost' => $p_price,
                // 'rtl_min' => $s_price,
                // 'rtl_max' => $s_price,
                // 'ws_min' => $w_price,
                // 'ws_max' => $w_price,
                'tkn' => $tkn_no,
                'List_price' => $p_price,
                'date' => date('Y-m-d'),
            );

            $grnTknModel = new GrnTknModel();
            $grn_data = $grnTknModel->where(array('inv' => $inv_no, 'stock_card' => $item_code, 'tkn' => $tkn_no))->countAllResults();

            if ($grn_data == 0) {
                $grnTknModel->insert($data_add);
                return json_encode(array('status' => 200, 'message' => 'Successfully Added!'));
            } else {
                return json_encode(array('status' => 409, 'message' => 'Item Already Exist!'));
            }
        } catch (\Exception $e) {
            return json_encode(array('status' => 500, 'message' => $e->getMessage()));
        }
    }

    public function get_grn_items($tkn, $sup, $inv)
    {
        try {
            // URL decode the parameters
            $tkn = urldecode($tkn);
            $sup = urldecode($sup);
            $inv = urldecode($inv);

            $grnTknModel = new GrnTknModel();
            $itemDetails = $grnTknModel->select('grn_tkn.*, stock_card.Descrip, stock_card.Stock_Card')
                ->join('stock_card', 'stock_card.Stock_Card = grn_tkn.stock_card', 'left')
                ->where([
                    'grn_tkn.inv' => $inv,
                    'grn_tkn.sup' => $sup,
                    'grn_tkn.tkn' => $tkn
                ])->findAll();

            return json_encode(array('status' => 200, 'data' => $itemDetails));
        } catch (\Exception $e) {
            return json_encode(array('status' => 500, 'message' => $e->getMessage()));
        }
    }

    public function get_grn_items_by_params()
    {
        try {
            $tkn = $this->request->getGet('tkn');
            $sup = $this->request->getGet('sup');
            $inv = $this->request->getGet('inv');

            $grnTknModel = new GrnTknModel();
            $itemDetails = $grnTknModel->select('grn_tkn.*, stock_card.Descrip, stock_card.Stock_Card')
                ->join('stock_card', 'stock_card.Stock_Card = grn_tkn.stock_card', 'left')
                ->where([
                    'grn_tkn.inv' => $inv,
                    'grn_tkn.sup' => $sup,
                    'grn_tkn.tkn' => $tkn
                ])->findAll();

            return json_encode(array('status' => 200, 'data' => $itemDetails));
        } catch (\Exception $e) {
            return json_encode(array('status' => 500, 'message' => $e->getMessage()));
        }
    }

    public function update_qty()
    {
        try {
            $inv = $this->request->getPost('inv');
            $sup = $this->request->getPost('sup');
            $tkn = $this->request->getPost('tkn');
            $stock_card = $this->request->getPost('stock_card');
            $qty = $this->request->getPost('qty');

            $grnTknModel = new GrnTknModel();
            $grnTknModel->where([
                'inv' => $inv,
                'sup' => $sup,
                'tkn' => $tkn,
                'stock_card' => $stock_card
            ])->set(['qty' => $qty])->update();

            return json_encode(array('status' => 200, 'message' => 'Successfully Updated!'));
        } catch (\Exception $e) {
            return json_encode(array('status' => 500, 'message' => $e->getMessage()));
        }
    }

    public function update_foc_qty()
    {
        try {
            $inv = $this->request->getPost('inv');
            $sup = $this->request->getPost('sup');
            $tkn = $this->request->getPost('tkn');
            $stock_card = $this->request->getPost('stock_card');
            $foc_qty = $this->request->getPost('foc_qty');

            $grnTknModel = new GrnTknModel();
            $grnTknModel->where([
                'inv' => $inv,
                'sup' => $sup,
                'tkn' => $tkn,
                'stock_card' => $stock_card
            ])->set(['foc' => $foc_qty])->update();

            return json_encode(array('status' => 200, 'message' => 'FOC Quantity Updated Successfully!'));
        } catch (\Exception $e) {
            return json_encode(array('status' => 500, 'message' => $e->getMessage()));
        }
    }

    public function remove_item()
    {
        try {
            $inv = $this->request->getPost('inv');
            $sup = $this->request->getPost('sup');
            $tkn = $this->request->getPost('tkn');
            $stock_card = $this->request->getPost('stock_card');

            $grnTknModel = new GrnTknModel();
            $grnTknModel->where([
                'inv' => $inv,
                'sup' => $sup,
                'tkn' => $tkn,
                'stock_card' => $stock_card
            ])->delete();

            return json_encode(array('status' => 200, 'message' => 'Successfully Removed!'));
        } catch (\Exception $e) {
            return json_encode(array('status' => 500, 'message' => $e->getMessage()));
        }
    }

    public function finalize_grn_by_params()
    {
        $tkn_no = $this->request->getPost('inv');
        $supplier = $this->request->getPost('sup');
        $inv_no = $this->request->getPost('tkn');

        return $this->finalize_grn($tkn_no, $supplier, $inv_no);
    }

    public function finalize_grn($tkn_no, $supplier, $inv_no)
    {
        try {
            $grnTknOrderModel = new GrnTknOrderModel();
            $order = $grnTknOrderModel->where('order_no', $inv_no)->where('sup_id', $supplier)->where('inv_no', $tkn_no)->first();
            if (empty($order)) {
                return json_encode(array('status' => 400, 'message' => 'Order not found!'));
            }

            $invoiceNumberModel = new InvoiceNoModel();
            $ref = $invoiceNumberModel->incrementStockNo();
            $grn_no = $invoiceNumberModel->GetNextPurchaseNumber();

            $token = $grnTknOrderModel->where('inv_no', $tkn_no)->first();

            if (empty($token)) {
                return json_encode(array('status' => 400, 'message' => 'Token not found!'));
            }

            $date = $token['date'];
            $order_no = $order['order_no'];
            $sup_id = $order['sup_id'];



            // stock order
            $data = array(
                'P_Order' => $order_no,
                'Ref' => $ref,
                'Sup_Acc' => $sup_id,
                'Job' => $inv_no,
                'Date' => $date,
                'GRN_no' => $grn_no,
                'GRN_Type' => 'GRN',
                'Type' => 'GRN',
                'Stock_date' => $date,
                'Login' => session()->get('username'),
            );

            $stockOrderModel = new StockOrderModel();

            if ($stockOrderModel->insert($data)) {
                // get grn item list

                // delete inv no from grn_tkn_order
                $grnTknOrderModel->where('inv_no', $tkn_no)->delete();

                $grnTknModel = new GrnTknModel();
                $items = $grnTknModel->where('inv', $tkn_no)->findAll();

                $i = 0;
                foreach ($items as $item) {
                    $paid_qty = $item['qty'];
                    $foc_qty = $item['foc'] ?? 0;

                    // get current stock qty
                    $stockModel = new StockModel();
                    $stock = $stockModel->select('SUM(Dr_Qty) - SUM(Cr_Qty) AS bal')->where('Stock_Card', $item['stock_card'])->first();
                    $old_qty = 0;
                    if (!empty($stock)) {
                        $old_qty = $stock['bal'];
                    }

                    // get current avg cost
                    $stockCostModel = new StockCostModel();
                    $stockCost = $stockCostModel->where('Stock_Card', $item['stock_card'])->first();
                    $rowcount_avg = $stockCostModel->where('Stock_Card', $item['stock_card'])->countAllResults();
                    $old_avg_cost = 0;
                    if (!empty($stockCost)) {
                        $avg_cost = $stockCost['Avg_Cost'];
                        $old_avg_cost = $avg_cost;
                    }

                    // Calculate new avg cost - ONLY using paid quantity
                    // FOC items are EXCLUDED from the average cost calculation
                    if ($paid_qty > 0) {
                        $new_avg_cost = (($old_qty * $old_avg_cost) + ($paid_qty * $item['ucost'])) / ($old_qty + $paid_qty);
                    } else {
                        $new_avg_cost = $old_avg_cost; // No paid items, cost unchanged
                    }

                    $cost_data = array(
                        'Avg_Cost' => $new_avg_cost,
                        'Last_Cost' => $item['ucost'],
                        'usern' => session()->get('username'),
                        'date' => date('Y-m-d H:i:s')
                    );

                    // changes to stock cost table
                    if ($rowcount_avg == 0) {
                        $cost_data['Stock_Card'] = $item['stock_card'];
                        $stockCostModel->insert($cost_data);
                    } else {
                        $stockCostModel->where('Stock_Card', $item['stock_card'])->set($cost_data)->update();
                    }

                    // update stock card table with new prices
                    $stockCardModel = new StockCardModel();
                    // $stockCardModel->where('Stock_Card', $item['stock_card'])->set([
                    //     'wPrice' => $item['ws_max'],
                    //     'Sales_P_Max' => $item['rtl_min']
                    // ])->update();

                    // Insert stock record for PAID quantity (if any)
                    if ($paid_qty > 0) {
                        $stock_data_paid = array(
                            'Stock_Card' => $item['stock_card'],
                            'Ref' => $ref,
                            'Unit_Cost' => $item['ucost'],
                            'Dr_Qty' => $paid_qty,
                            'tym' => $item['date'],
                            'Status' => 'N',
                            'Stores' => 0,
                            'foc' => 0, // Paid items
                            'pPrice' => $item['ucost'],
                            'List_price' => 0.00,
                            'Dis_price' => 0.00,
                            'L_dis' => 0,
                            'txt' => 'GRN - ' . $inv_no,
                        );
                        $stockModel->insert($stock_data_paid);
                        $i++;
                    }

                    // Insert stock record for FOC quantity (if any)
                    if ($foc_qty > 0) {
                        $stock_data_foc = array(
                            'Stock_Card' => $item['stock_card'],
                            'Ref' => $ref,
                            'Unit_Cost' => 0.00, // FOC has zero cost
                            'Dr_Qty' => $foc_qty,
                            'tym' => $item['date'],
                            'Status' => 'N',
                            'Stores' => 0,
                            'foc' => $foc_qty, // FOC items
                            'pPrice' => 0.00,
                            'List_price' => 0.00,
                            'Dis_price' => 0.00,
                            'L_dis' => 0,
                            'txt' => 'GRN - ' . $inv_no,
                        );
                        $stockModel->insert($stock_data_foc);
                        $i++;
                    }
                }

                return json_encode(array('status' => 200, 'message' => 'Successfully Finalized!', 'itemCount' => $i));
            }
            return json_encode(array('status' => 400, 'message' => 'Failed to finalize!'));
        } catch (\Exception $e) {
            log_message('error', $e);
            return json_encode(array('status' => 500, 'message' => $e->getMessage()));
        }
    }
}
