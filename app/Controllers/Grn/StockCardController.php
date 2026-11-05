<?php

namespace App\Controllers\Grn;

use App\Controllers\BaseController;
use App\Libraries\MenuLoader;
use App\Models\Grn\StockCardModel;

class StockCardController extends BaseController
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Colombo');
    }

    /**
     * Stock Card Model = ['Stock_Book', 'Stock_Cat', 'Sales_P', 'Date', 'wPrice', 'Sales_P_Max', 'wPrice_Max', 'Rack_Location', 'Stock_Card', 'reorder', 'pp', 'vat', 'dis', 'tp', 'iorder', 'Max_ordr', 'itm', 'Bar_code', 'flag', 'units', 'foc', 'Descrip', 'remarks']
     */

    // route : base_url/inventory/item_registry
    public function index()
    {
        $data = array();

        // Load menu data using the custom library
        $menuLoader = new MenuLoader();
        $data = $menuLoader->loadMenuData();

        // get all items from the stock card
        $stockCardModel = new StockCardModel();
        $data['items'] = $stockCardModel->select('stock_card.*, stock_book.Name as main_cat, stock_cat.Name as sub_cat, entrance_fee.name as entrance_fee_name, entrance_fee.code as entrance_fee_code')
            ->join('stock_book', 'stock_book.Id = stock_card.Stock_Book')
            ->join('stock_cat', 'stock_cat.Id = stock_card.Stock_Cat')
            ->join('entrance_fee', 'entrance_fee.id = stock_card.entrance_fee_id', 'left')
            ->findAll();

        return view('pages/grn/item_registry', $data);
    }

    // route : base_url/inventory/item_registry/create
    public function create()
    {
        $main_cat = $this->request->getPost('main_cat');
        $sub_cat = $this->request->getPost('sub_cat');
        $description = $this->request->getPost('description');
        $code = $this->request->getPost('code');
        $reorder = $this->request->getPost('reorder');
        $purchase_price = $this->request->getPost('purchase_price');
        $entrance_fee_id = $this->request->getPost('entrance_fee_id') ?: null;
        // $sales_price = $this->request->getPost('sales_price');
        // $whole_sale_price = $this->request->getPost('whole_sale_price');

        // validation
        $validation = \Config\Services::validation();
        $validation->setRules([
            'main_cat' => 'required',
            'sub_cat' => 'required',
            'description' => 'required',
            'code' => 'required',
            'reorder' => 'required',
            'purchase_price' => 'required',
            // 'sales_price' => 'required',
            // 'whole_sale_price' => 'required',
        ]);

        if (!$validation->run($this->request->getPost())) {
            return json_encode(array('status' => 400, 'message' => 'Validation Error!', 'errors' => $validation->getErrors()));
        }

        $id = $this->createStockCard($main_cat, $sub_cat, $description, $code, $reorder, $purchase_price, $entrance_fee_id);

        if ($id == 0) return json_encode(array('status' => 409, 'message' => 'An item with the same code already exists!'));

        return json_encode(array('status' => 201, 'message' => 'Item created successfully!', 'id' => $id));
    }

    // create new stock card
    public function createStockCard($main_cat, $sub_cat, $description, $code, $reorder, $purchase_price, $entrance_fee_id = null)
    {
        // check if main_cat exists, if yes get id else create it an get id.
        $StockBookAndCatController = new StockBookAndCatController();
        $main_cat_id = $StockBookAndCatController->isStockBookExist($main_cat);
        if (empty($main_cat_id)) $main_cat_id = $StockBookAndCatController->createStockBook($main_cat, $main_cat);
        else $main_cat_id = $main_cat_id['Id'];

        // check if sub_cat exists, if yes get id else create it an get id.
        $sub_cat_id = $StockBookAndCatController->isStockCatExist($sub_cat);
        if (empty($sub_cat_id)) $sub_cat_id = $StockBookAndCatController->createStockCat($sub_cat, $sub_cat);
        else $sub_cat_id = $sub_cat_id['Id'];

        $code = trim($code);

        $stockCardModel = new StockCardModel();
        $stockCard = $this->isItemExist($code);
        if (!empty($stockCard)) return 0; // conflict
        $id = $stockCardModel->insert([
            'Stock_Book' => $main_cat_id,
            'Stock_Cat' => $sub_cat_id,
            'Descrip' => $description,
            'Stock_Card' => $code,
            'reorder' => $reorder,
            'pp' => $purchase_price,
            'entrance_fee_id' => $entrance_fee_id,
            // 'Sales_P' => $sales_price,
            // 'Sales_P_Max' => $sales_price,
            // 'wPrice' => $whole_sale_price,
            // 'wPrice_Max' => $whole_sale_price,
            'Date' => date('Y-m-d H:i:s'),
        ], true);

        // log_message('critical', $stockCardModel->getLastQuery());

        return $id;
    }

    public function isItemExist($Stock_Card)
    {
        $stockCardModel = new StockCardModel();
        return $stockCardModel->where('Stock_Card', $Stock_Card)->first();
    }

    public function all_items()
    {
        $stockCardModel = new StockCardModel();
        $items = $stockCardModel->findAll();
        return json_encode($items);
    }

    public function getItem($id){
        $stockCardModel = new StockCardModel();
        $item = $stockCardModel->select('stock_card.*, stock_book.Name as main_cat, stock_cat.Name as sub_cat, entrance_fee.name as entrance_fee_name, entrance_fee.code as entrance_fee_code, entrance_fee.location as entrance_fee_location')
            ->join('stock_book', 'stock_book.Id = stock_card.Stock_Book')
            ->join('stock_cat', 'stock_cat.Id = stock_card.Stock_Cat')
            ->join('entrance_fee', 'entrance_fee.id = stock_card.entrance_fee_id', 'left')
            ->where('stock_card.ID', $id)
            ->first();
        return json_encode($item);
    }

    public function update()
    {
        $id = $this->request->getPost('id');
        $main_cat = $this->request->getPost('main_cat');
        $sub_cat = $this->request->getPost('sub_cat');
        $description = $this->request->getPost('description');
        $code = $this->request->getPost('code');
        $reorder = $this->request->getPost('reorder');
        $purchase_price = $this->request->getPost('purchase_price');
        $entrance_fee_id = $this->request->getPost('entrance_fee_id') ?: null;
        // $sales_price = $this->request->getPost('sales_price');
        // $whole_sale_price = $this->request->getPost('whole_sale_price');

        // validation
        $validation = \Config\Services::validation();
        $validation->setRules([
            'id' => 'required',
            'main_cat' => 'required',
            'sub_cat' => 'required',
            'description' => 'required',
            'code' => 'required',
            'reorder' => 'required',
            'purchase_price' => 'required',
            // 'sales_price' => 'required',
            // 'whole_sale_price' => 'required',
        ]);

        if (!$validation->run($this->request->getPost())) {
            return json_encode(array('status' => 400, 'message' => 'Validation Error!', 'errors' => $validation->getErrors()));
        }

        $id = $this->updateStockCard($id, $main_cat, $sub_cat, $description, $code, $reorder, $purchase_price, $entrance_fee_id);

        if ($id == 0) return json_encode(array('status' => 409, 'message' => 'An item with the same code already exists!'));

        return json_encode(array('status' => 200, 'message' => 'Item updated successfully!', 'id' => $id));
    }

    public function updateStockCard($id, $main_cat, $sub_cat, $description, $code, $reorder, $purchase_price, $entrance_fee_id = null)
    {
        // check if main_cat exists, if yes get id else create it an get id.
        $StockBookAndCatController = new StockBookAndCatController();
        $main_cat_id = $StockBookAndCatController->isStockBookExist($main_cat);
        if (empty($main_cat_id)) $main_cat_id = $StockBookAndCatController->createStockBook($main_cat, $main_cat);
        else $main_cat_id = $main_cat_id['Id'];

        // check if sub_cat exists, if yes get id else create it an get id.
        $sub_cat_id = $StockBookAndCatController->isStockCatExist($sub_cat);
        if (empty($sub_cat_id)) $sub_cat_id = $StockBookAndCatController->createStockCat($sub_cat, $sub_cat);
        else $sub_cat_id = $sub_cat_id['Id'];

        $code = trim($code);

        $stockCardModel = new StockCardModel();
        $stockCard = $this->isItemExist($code);
        if (!empty($stockCard) && $stockCard['ID'] != $id) return 0; // conflict
        $stockCardModel->update($id, [
            'Stock_Book' => $main_cat_id,
            'Stock_Cat' => $sub_cat_id,
            'Descrip' => $description,
            'Stock_Card' => $code,
            'reorder' => $reorder,
            'pp' => $purchase_price,
            'entrance_fee_id' => $entrance_fee_id,
            // 'Sales_P' => $sales_price,
            // 'Sales_P_Max' => $sales_price,
            // 'wPrice' => $whole_sale_price,
            // 'wPrice_Max' => $whole_sale_price,
            'Date' => date('Y-m-d H:i:s'),
        ]);

        return $id;
    }

    public function readCsv(){
        $csv_file =  WRITEPATH . 'uploads/inv_csv.csv';
        $file = fopen($csv_file, 'r');
        $data = array();
        while (($line = fgetcsv($file)) !== FALSE) {
            $data[] = $line;
        }
        fclose($file);
        $x = 1;
        foreach ($data as $row){
            $this->createStockCard('Default', 'Default', trim($row[0]), $x, 0, 0, 0, 0);
            $x++;
        }
        return json_encode(array('status' => 200, 'message' => $x . ' Items added successfully!'));
    }

    // route : base_url/inventory/item_registry/get_entrance_fees
    public function get_entrance_fees()
    {
        $term = $this->request->getGet('term');

        $entranceFeeModel = new \App\Models\EntranceFeeModel();

        $fees = $entranceFeeModel
            ->like('name', $term)
            ->orLike('code', $term)
            ->orLike('location', $term)
            ->findAll(10);

        $results = [];
        foreach ($fees as $fee) {
            $results[] = [
                'id' => $fee['id'],
                'label' => $fee['name'] . ' (' . $fee['code'] . ') - ' . $fee['location'],
                'value' => $fee['name'],
                'code' => $fee['code'],
                'fee' => $fee['entranceFee'],
                'location' => $fee['location']
            ];
        }

        return json_encode($results);
    }
}
